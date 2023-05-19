<?php

namespace App\Domain\Worldwide\Services\SalesOrder;

use App\Domain\Address\Enum\AddressType;
use App\Domain\VendorServices\Services\CachingOauthClient;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit\SubmitOrderAddressData;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit\SubmitOrderLineData;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit\SubmitSalesOrderData;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit\SubmitSalesOrderResult;
use App\Domain\Worldwide\Enum\SalesOrderStatus;
use Illuminate\Config\Repository as Config;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SubmitSalesOrderService
{
    const USER_AGENT = 'EQ';

    const BUSINESS_DIVISION = 'Worldwide';

    public function __construct(
        protected Config $config,
        protected ValidatorInterface $validator,
        protected CachingOauthClient $oauthClient,
        protected HttpFactory $http
    ) {
    }

    public function processSalesOrderDataSubmission(SubmitSalesOrderData $data): SubmitSalesOrderResult
    {
        $violations = $this->validator->validate($data);

        $violations->addAll($this->validator->validate($data->customer_data));

        foreach ($data->order_lines_data as $lineData) {
            $violations->addAll($this->validator->validate($lineData));
        }

        if (count($violations)) {
            $violationMessages = [];

            foreach ($violations as $violation) {
                /* @var ConstraintViolationInterface $violation */
                $violationMessages[$violation->getMessage()] = true;
            }

            return new SubmitSalesOrderResult([
                'status' => SalesOrderStatus::FAILURE,
                'status_reason' => 'Sales Order Data failed the internal validation, the errors are: '.implode(' ', array_keys($violationMessages)),
            ]);
        }

        $url = rtrim($this->getBaseUrl(), '/').'/'.ltrim($this->config->get('services.vs.submit_sales_order_route'), '/');

        $token = $this->oauthClient->getAccessToken();

        $postData = $this->mapSubmitSalesOrderDataToPostData($data);

        $response = $this->http
            ->acceptJson()
            ->withToken($token)
            ->post(url: $url, data: $postData);

        if ($response->status() >= 400) {
            return new SubmitSalesOrderResult([
                'status' => SalesOrderStatus::FAILURE,
                'status_reason' => $this->getResponseStatusReason($response),
            ]);
        }

        return new SubmitSalesOrderResult([
            'status' => SalesOrderStatus::SENT,
        ]);
    }

    private function getResponseStatusReason(Response $response): string
    {
        $errorDetails = Arr::wrap($response->json('ErrorDetails'));
        $validationErrors = Arr::wrap($response->json('Error.original'));
        $errorMessage = $response->json('Error.original.message');

        if (is_string($errorMessage) && 'server error' === mb_strtolower($errorMessage)) {
            return $errorMessage;
        }

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
        return [
            'data' => [
                'said' => $salesOrderData->service_agreement_id,
                'from_date' => $salesOrderData->from_date,
                'to_date' => $salesOrderData->to_date,
                'post_sales_id' => $salesOrderData->post_sales_id,
                'company_id' => $salesOrderData->company_id,
                'address_1' => $salesOrderData->invoice_address?->address_1 ?? null,
                'address_2' => $salesOrderData->invoice_address?->address_2 ?? null,
                'city' => $salesOrderData->invoice_address?->city ?? null,
                'state' => $salesOrderData->invoice_address?->state ?? null,
                'post_code' => $salesOrderData->invoice_address?->post_code ?? null,
                'country_code' => $salesOrderData->invoice_address?->country_code ?? null,
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
                'customer_name' => $salesOrderData->customer_data->customer_name,
                'sales_person_name' => $salesOrderData->sales_person_name,
                'company_name' => $salesOrderData->bc_company_name,
                'vendor' => $salesOrderData->vendor_short_code,
                'sales_lines' => collect($salesOrderData->order_lines_data)->map(static fn (
                    SubmitOrderLineData $lineData
                ): array => [
                    'service_sku' => $lineData->service_sku,
                    'service_description' => $lineData->service_description,
                    'serial_number' => $lineData->serial_number,
                    'sku' => $lineData->sku,
                    'quantity' => $lineData->quantity,
                    'unit_price' => $lineData->buy_price,
                    'machine_country_code' => $lineData->machine_country_code,
                    'sales_group' => sprintf('%s %s', self::BUSINESS_DIVISION, $salesOrderData->contract_type),
                    'vendor_name' => $lineData->vendor_short_code,
                ]),
            ],
        ];
    }

    protected function getBaseUrl(): string
    {
        return $this->config->get('services.vs.url') ?? '';
    }
}
