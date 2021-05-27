<?php

namespace App\Services\SalesOrder;

use App\DTO\SalesOrder\Submit\SubmitOrderAddressData;
use App\DTO\SalesOrder\Submit\SubmitOrderLineData;
use App\DTO\SalesOrder\Submit\SubmitSalesOrderData;
use App\DTO\SalesOrder\Submit\SubmitSalesOrderResult;
use App\Enum\SalesOrderStatus;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Illuminate\Config\Repository as Config;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SubmitSalesOrderService
{
    const USER_AGENT = 'EQ';

    const BUSINESS_DIVISION = 'Worldwide';

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

    public function processSalesOrderDataSubmission(SubmitSalesOrderData $data): SubmitSalesOrderResult
    {
        $violations = $this->validator->validate($data);

        $violations->addAll($this->validator->validate($data->customer_data));

        foreach ($data->addresses_data as $addressData) {
            $violations->addAll($this->validator->validate($addressData));
        }

        foreach ($data->order_lines_data as $lineData) {
            $violations->addAll($this->validator->validate($lineData));
        }

        if (count($violations)) {
            $violationMessages = [];

            foreach ($violations as $violation) {
                /** @var ConstraintViolationInterface $violation */
                $violationMessages[$violation->getMessage()] = true;
            }

            return new SubmitSalesOrderResult([
                'status' => SalesOrderStatus::FAILURE,
                'status_reason' => 'Sales Order Data failed the internal validation, the errors are: '.implode(' ', array_keys($violationMessages))
            ]);
        }

        $url = rtrim($this->getBaseUrl(), '/').'/'.ltrim($this->config->get('services.vs.submit_sales_order_route'), '/');

        $token = $this->issueBearerToken();

        $postData = $this->mapSubmitSalesOrderDataToPostData($data);

        $response = $this->client->post($url, [
            RequestOptions::JSON => $postData,
            RequestOptions::HEADERS => [
                'Authorization' => "Bearer $token",
                'Accept' => 'application/json',
            ]
        ]);

        if ($response->getStatusCode() >= 400) {

            return new SubmitSalesOrderResult([
                'status' => SalesOrderStatus::FAILURE,
                'status_reason' => $this->getResponseStatusReason($response)
            ]);

        }

        return new SubmitSalesOrderResult([
            'status' => SalesOrderStatus::SENT,
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

    protected function mapSubmitSalesOrderDataToPostData(SubmitSalesOrderData $salesOrderData): array
    {
        /** @var SubmitOrderAddressData|null $invoiceAddress */
        $invoiceAddress = value(function () use ($salesOrderData): ?SubmitOrderAddressData {

            foreach ($salesOrderData->addresses_data as $addressData) {
                if ($addressData->address_type === 'Invoice') {
                    return $addressData;
                }
            }

            return null;

        });

        return [
            'data' => [
                'said' => $salesOrderData->service_agreement_id,
                'from_date' => $salesOrderData->from_date,
                'to_date' => $salesOrderData->to_date,
                'post_sales_id' => $salesOrderData->post_sales_id,
                'company_id' => $salesOrderData->company_id,
                'address_1' => $invoiceAddress->address_1 ?? null,
                'address_2' => $invoiceAddress->address_2 ?? null,
                'city' => $invoiceAddress->city ?? null,
                'county' => $invoiceAddress->state ?? null,
                'post_code' => $invoiceAddress->post_code ?? null,
                'country_code' => $invoiceAddress->country_code ?? null,
                'phone_no' => $salesOrderData->customer_data->phone_no,
                'email' => $salesOrderData->customer_data->email,
                'company_reg_no' => $salesOrderData->customer_data->company_reg_no,
                'vat_reg_no' => $salesOrderData->customer_data->vat_reg_no,
                'order_no' => $salesOrderData->order_no,
                'user_agent' => self::USER_AGENT,
                'in_contract' => $salesOrderData->contract_type,
                'created_date' => $salesOrderData->order_date,
                'currency_code' => $salesOrderData->customer_data->currency_code,
                'exchange_rate' => $salesOrderData->exchange_rate,
                'customer_po' => $salesOrderData->customer_po,
                'name' => $salesOrderData->customer_data->customer_name,
                'sales_person_name' => $salesOrderData->sales_person_name,
                'nav_company' => $salesOrderData->bc_company_name,
                'vendor' => $salesOrderData->vendor_short_code,
                'sales_line' => array_map(fn(SubmitOrderLineData $lineData): array => [
                    'service_sku' => $lineData->service_sku,
                    'service_description' => $lineData->service_description,
                    'serial_number' => $lineData->serial_number,
                    'sku' => $lineData->sku,
                    'quantity' => $lineData->quantity,
                    'unit_price' => $lineData->unit_price,
                    'machine_country_code' => $lineData->machine_country_code,
                    'sales_group' => sprintf('%s %s', self::BUSINESS_DIVISION, $salesOrderData->contract_type),
                    'short_name' => $lineData->vendor_short_code,
                ], $salesOrderData->order_lines_data)
            ]
        ];
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
