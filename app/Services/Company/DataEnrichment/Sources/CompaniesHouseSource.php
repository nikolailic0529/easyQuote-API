<?php

namespace App\Services\Company\DataEnrichment\Sources;

use App\Services\Company\DataEnrichment\Enum\CompanyStatusEnum;
use App\Services\Company\DataEnrichment\Models\CompanyAddress;
use App\Services\Company\DataEnrichment\Models\CompanyProfile;
use Illuminate\Http\Client\Factory as Client;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use function App\Foundation\String\mb_levenshtein_ratio;

class CompaniesHouseSource implements Source
{
    protected string $profileEndpoint = 'company';
    protected string $officersEndpoint = 'officers';
    protected string $searchEndpoint = 'search/companies';

    protected float $fuzzyMatchThreshold = 0.7;

    public function __construct(
        protected readonly Client $client,
        protected readonly array $config,
    ) {
    }

    public function find(string $name): ?CompanyProfile
    {
        $response = $this->client->acceptJson()
            ->withBasicAuth($this->getApiUsername(), $this->getApiPassword())
            ->get($this->buildSearchUrl(), ['q' => $name]);

        $response->throw();

        $items = $response->collect('items');

        if ($items->isEmpty()) {
            return null;
        }

        $profile = $this->fuzzyMatchItem($items, $name);

        if (null === $profile) {
            return null;
        }

        return $this->get($profile['company_number']);
    }

    protected function fuzzyMatchItem(Collection $items, string $name): ?array
    {
        $name = $this->normalizeSearchTerm($name);

        return $items->lazy()
            ->map(function (array $item) use ($name): array {
                $item['_title'] = $this->normalizeSearchTerm($item['title'] ?? '');
                $item['_distance_rel'] = mb_levenshtein_ratio($item['_title'], $name);

                $item['_active'] = ('active' === $item['company_status']);

                return $item;
            })
            ->filter(function (array $item): bool {
                return $item['_distance_rel'] > $this->fuzzyMatchThreshold;
            })
            ->sortBy([
                ['_distance_rel', 'desc'],
                ['_active', 'desc'],
            ])
            ->first();
    }

    protected function normalizeSearchTerm(string $term): string
    {
        $term = mb_strtolower($term ?? '');

        $term = str_replace(['limited', 'ltd', 'holdings', '(', ')'], '', $term);

        return trim(preg_replace('#\s+#', ' ', $term));
    }

    /**
     * @throws RequestException
     */
    public function get(string $number): ?CompanyProfile
    {
        $profileResponse = $this->client->acceptJson()
            ->withBasicAuth($this->getApiUsername(), $this->getApiPassword())
            ->get($this->buildProfileUrl($number));

        if ($profileResponse->status() === 404) {
            return null;
        }

        $profileResponse->throw();

        $officersResponse = $this->client->acceptJson()
            ->withBasicAuth($this->getApiUsername(), $this->getApiPassword())
            ->get($this->buildOfficersUrl($number));

        $officersResponse->throw();

        $address = new CompanyAddress(
            locality: $profileResponse->json('registered_office_address.locality'),
            postCode: $profileResponse->json('registered_office_address.postal_code'),
            address1: $profileResponse->json('registered_office_address.address_line_1'),
            country: $profileResponse->json('registered_office_address.country'),
        );

        return new CompanyProfile(
            registeredNumber: $profileResponse->json('company_number'),
            name: $profileResponse->json('company_name'),
            employeesNumber: (int) $officersResponse->json('total_results'),
            creationDate: Carbon::parse($profileResponse->json('date_of_creation')),
            sicCodes: $profileResponse->json('sic_codes') ?? [],
            status: CompanyStatusEnum::from($profileResponse->json('company_status')),
            address: $address,
        );
    }

    protected function getApiUsername(): string
    {
        return $this->config['username'] ?? '';
    }

    protected function getApiPassword(): string
    {
        return $this->config['password'] ?? '';
    }

    protected function buildProfileUrl(string $number): string
    {
        return rtrim($this->getApiUrl(), '/').'/'.$this->profileEndpoint.'/'.$number;
    }

    protected function getApiUrl(): string
    {
        return $this->config['url'] ?? '';
    }

    protected function buildOfficersUrl(string $number): string
    {
        return rtrim($this->getApiUrl(), '/').'/'.$this->profileEndpoint.'/'.$number.'/'.$this->officersEndpoint;
    }

    protected function buildSearchUrl(): string
    {
        return rtrim($this->getApiUrl(), '/').'/'.$this->searchEndpoint;
    }

    public function getSupportedCountries(): array
    {
        return ['GB'];
    }
}