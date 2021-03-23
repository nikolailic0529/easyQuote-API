<?php

namespace App\Services\WorldwideQuote;

use App\DTO\WorldwideQuote\AssetServiceLookupDataCollection;
use App\DTO\WorldwideQuote\AssetServiceLookupResult;
use App\Models\Data\Currency;
use App\Services\Exceptions\ValidationException;
use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Config\Repository as Config;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Arr;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AssetServiceLookupService
{
    const DEFAULT_CURRENCY_CODE = 'EUR';

    const SUPPORTED_VENDORS = ['HPE', 'LEN'];

    protected ValidatorInterface $validator;

    protected Config $config;

    protected Client $client;

    private array $currenciesBuffer = [];

    public function __construct(ValidatorInterface $validator, Config $config)
    {
        $this->validator = $validator;
        $this->config = $config;
        $this->client = new Client([
            'http_errors' => false
        ]);
    }

    /**
     * @param AssetServiceLookupDataCollection $batchLookupData
     * @return array|AssetServiceLookupResult[]
     * @throws ValidationException
     */
    public function performBatchWarrantyLookup(AssetServiceLookupDataCollection $batchLookupData): array
    {
        foreach ($batchLookupData as $assetLookupData) {
            $violations = $this->validator->validate($assetLookupData);

            if (count($violations)) {
                throw new ValidationException($violations);
            }
        }


        $warrantyRequests = [];
        $supportRequests = [];

        $token = $this->issueBearerToken();

        foreach ($batchLookupData as $assetLookupData) {
            $warrantyLookupUrl = $this->buildVendorWarrantyLookupUrl(
                $assetLookupData->vendor_short_code,
                $assetLookupData->serial_no,
                $assetLookupData->sku,
                $assetLookupData->country_code
            );

            $supportLookupUrl = $this->buildVendorSupportLookupUrl(
                $assetLookupData->vendor_short_code,
                $assetLookupData->sku,
                $assetLookupData->country_code,
                $assetLookupData->currency_code
            );

            $warrantyRequests[$assetLookupData->asset_id] = new Request('GET', $warrantyLookupUrl, [
                'Authorization' => "Bearer $token"
            ]);

            $supportRequests[$assetLookupData->asset_id] = new Request('GET', $supportLookupUrl, [
                'Authorization' => "Bearer $token"
            ]);
        }

        $warrantyResponses = Pool::batch(
            $this->client,
            $warrantyRequests,
            [
                'concurrency' => 5
            ]
        );

        $supportResponses = Pool::batch(
            $this->client,
            $supportRequests,
            [
                'concurrency' => 5
            ]
        );

        $warrantyResponses = array_map(function (Response $response) {

            if ($response->getStatusCode() >= 400) {
                return [];
            }

            return json_decode((string)$response->getBody(), true);

        }, $warrantyResponses);

        $supportResponses = array_map(function (Response $response) {

            if ($response->getStatusCode() >= 400) {
                return [];
            }

            return json_decode((string)$response->getBody(), true);

        }, $supportResponses);

        $assetLookupResults = [];

        foreach ($batchLookupData as $lookupData) {
            $warrantyResponse = $warrantyResponses[$lookupData->asset_id] ?? [];
            $supportResponse = $supportResponses[$lookupData->asset_id] ?? [];

            $serviceLevelsArray = array_map(fn(array $supportData) => [
                'description' => $supportData['description'] ?? '',
                'price' => (float)($supportData['price'] ?? 0.0),
                'code' => $supportData['code'] ?? null,
            ], $supportResponse);

            $assetLookupResults[$lookupData->asset_id] = $assetServiceLookupResult = new AssetServiceLookupResult([
                'asset_id' => $lookupData->asset_id,
                'index' => $lookupData->asset_index,
                'serial_no' => $lookupData->serial_no,
                'model' => $warrantyResponse['model'] ?? null,
                'type' => $warrantyResponse['type'] ?? null,
                'sku' => $lookupData->sku,
                'product_name' => $warrantyResponse['description'] ?? null,
                'expiry_date' => $warrantyResponse['warranty_end_date'] ?? null,
                'vendor_short_code' => $lookupData->vendor_short_code,
                'currency_code' => Arr::get($supportResponse, '0.currency_code') ?? null,
                'country_code' => Arr::get($supportResponse, '0.country_code') ?? null,
                'service_levels' => $serviceLevelsArray
            ]);
        }

        return $assetLookupResults;
    }

    /**
     * Guess Service Level Data and populate accordingly
     * by matching the same serial number or sku of assets.
     *
     * @param AssetServiceLookupResult[] $assetLookupResults
     * @return void
     */
    public function guessServiceLevelDataOfAssetLookupResults(array $assetLookupResults): void
    {
        /** @var AssetServiceLookupResult[] $serialNoVendorResultDictionary */
        $serialNoVendorResultDictionary = [];

        /** @var AssetServiceLookupResult[] $skuVendorResultDictionary */
        $skuVendorResultDictionary = [];

        foreach ($assetLookupResults as $result) {
            if (!is_null($result->serial_no) && !empty($result->service_levels)) {
                $serialNoResultDictionary[$result->serial_no.$result->vendor_short_code] = $result;
            }

            if (!is_null($result->sku) && !empty($result->service_levels)) {
                $skuVendorResultDictionary[$result->sku.$result->vendor_short_code] = $result;
            }
        }

        foreach ($assetLookupResults as $result) {
            if (empty($result->service_levels)) {

                $matchingResult = $serialNoResultDictionary[$result->serial_no.$result->vendor_short_code] ??
                    $skuVendorResultDictionary[$result->sku.$result->vendor_short_code] ??
                    null;

                if (!is_null($matchingResult)) {

                    $result->service_levels = $matchingResult->service_levels;

                }

            }
        }


    }

    protected function issueBearerToken(): string
    {
        $url = rtrim($this->getBaseUrl(), '/').'/'.$this->config->get('services.vs.token_route');

        $response = $this->client->post(
            $url,
            [
                'form_params' => [
                    'client_id' => $this->config->get('services.vs.client_id'),
                    'client_secret' => $this->config->get('services.vs.client_secret'),
                    'grant_type' => 'client_credentials',
                    'scope' => '*'
                ]
            ]
        );

        $json = json_decode((string)$response->getBody(), true);

        return $json['access_token'] ?? '';
    }

    protected function buildVendorSupportLookupUrl(string $vendorShortCode, string $sku, string $countryCode, ?string $currencyCode = null): string
    {
        $uri = [
            'HPE' => $this->config->get('services.vs.support_lookup_routes.HPE'),
            'LEN' => $this->config->get('services.vs.support_lookup_routes.LEN'),
        ][$vendorShortCode];

        $uri = strtr($uri, [
            '{sku}' => $sku,
            '{country}' => $countryCode,
            '{currency}' => $currencyCode ?? $this->resolveCurrencyCodeOfCountry($countryCode)
        ]);

        return rtrim($this->getBaseUrl(), '/').'/'.ltrim($uri, '/');
    }

    protected function resolveCurrencyCodeOfCountry(string $countryCode): string
    {
        return $this->currenciesBuffer[$countryCode] ??= with($countryCode, function (string $countryCode) {
            $currencyCode = Currency::query()
                ->join('countries', function (JoinClause $join) {
                    $join->on('countries.default_currency_id', 'currencies.id');
                })
                ->where('countries.iso_3166_2', $countryCode)
                ->select('currencies.code')
                ->value('code');

            return $currencyCode ?? static::DEFAULT_CURRENCY_CODE;
        });
    }

    protected function buildVendorWarrantyLookupUrl(string $vendorShortCode, string $serial, string $sku = '', string $countryCode = ''): string
    {
        $uri = [
            'HPE' => $this->config->get('services.vs.service_routes.HPE'),
            'LEN' => $this->config->get('services.vs.service_routes.LEN'),
        ][$vendorShortCode];

        $uri = strtr($uri, [
            '{serial}' => $serial,
            '{sku}' => $sku,
            '{country}' => $countryCode,
        ]);

        return rtrim($this->getBaseUrl(), '/').'/'.$uri;
    }

    protected function getBaseUrl(): string
    {
        return $this->config->get('services.vs.url') ?? '';
    }
}
