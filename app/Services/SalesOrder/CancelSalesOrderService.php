<?php

namespace App\Services\SalesOrder;

use App\DTO\SalesOrder\Cancel\CancelSalesOrderData;
use App\DTO\SalesOrder\Cancel\CancelSalesOrderResult;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Illuminate\Config\Repository as Config;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CancelSalesOrderService
{
    protected Config $config;

    protected ValidatorInterface $validator;

    protected Client $client;

    public function __construct(Config $config, ValidatorInterface $validator, ClientInterface $client = null)
    {
        $this->config = $config;

        $this->validator = $validator;

        $this->client = $client ?? new Client([
                RequestOptions::HTTP_ERRORS => false
            ]);
    }

    public function processSalesOrderCancellation(CancelSalesOrderData $data): CancelSalesOrderResult
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            $violationMessages = [];

            foreach ($violations as $violation) {
                /** @var ConstraintViolationInterface $violation */
                $violationMessages[$violation->getMessage()] = true;
            }

            $violationMessagesString = implode(' ', array_keys($violationMessages));

            return new CancelSalesOrderResult([
                'response_ok' => false,
                'status_reason' => "Cancel Sales Order Data failed the internal validation, the errors are: $violationMessagesString"
            ]);
        }

        $url = $this->buildCancelSalesOrderUrl($data->sales_order_id);

        $token = $this->issueBearerToken();

        $response = $this->client->patch($url, [
            RequestOptions::JSON => [
                'status_reason' => $data->status_reason
            ],
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer $token",
                'Accept' => 'application/json',
            ]
        ]);

        if ($response->getStatusCode() >= 400) {

            return new CancelSalesOrderResult([
                'response_ok' => false,
                'status_reason' => $this->getResponseStatusReason($response)
            ]);

        }

        return new CancelSalesOrderResult([
            'response_ok' => true
        ]);
    }

    private function getResponseStatusReason(ResponseInterface $response): string
    {
        $json = json_decode((string)$response->getBody(), true);

        $errorDetails = Arr::wrap(Arr::get($json, 'ErrorDetails'));
        $validationErrors = Arr::wrap(Arr::get($json, 'Error.original'));

        if (!empty($validationErrors)) {
            return implode(' ', array_unique(Arr::flatten($validationErrors)));
        }

        if (!empty($errorDetails)) {
            return implode(' ', Arr::flatten($errorDetails));

        }

        return "Server responded with {$response->getStatusCode()} status code.";
    }

    protected function buildCancelSalesOrderUrl(string $id): string
    {
        $resource = strtr($this->config->get('services.vs.cancel_sales_order_route'), [
            '{id}' => $id
        ]);

        return rtrim($this->getBaseUrl(), '/').'/'.ltrim($resource, '/');
    }

    protected function issueBearerToken(): string
    {
        $url = rtrim($this->getBaseUrl(), '/').'/'.ltrim($this->config->get('services.vs.token_route'), '/');

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

    protected function getBaseUrl(): string
    {
        return $this->config->get('services.vs.url') ?? '';
    }
}
