<?php

namespace App\Domain\Worldwide\Services\SalesOrder;

use App\Domain\VendorServices\Services\CachingOauthClient;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\Cancel\CancelSalesOrderData;
use App\Domain\Worldwide\DataTransferObjects\SalesOrder\Cancel\CancelSalesOrderResult;
use Illuminate\Config\Repository as Config;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CancelSalesOrderService
{
    public function __construct(protected Config $config,
                                protected ValidatorInterface $validator,
                                protected CachingOauthClient $oauthClient,
                                protected Factory $client)
    {
    }

    public function processSalesOrderCancellation(CancelSalesOrderData $data): CancelSalesOrderResult
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            $violationMessages = [];

            foreach ($violations as $violation) {
                /* @var ConstraintViolationInterface $violation */
                $violationMessages[$violation->getMessage()] = true;
            }

            $violationMessagesString = implode(' ', array_keys($violationMessages));

            return new CancelSalesOrderResult([
                'response_ok' => false,
                'status_reason' => "Cancel Sales Order Data failed the internal validation, the errors are: $violationMessagesString",
            ]);
        }

        $url = $this->buildCancelSalesOrderUrl($data->sales_order_id);

        $token = $this->oauthClient->getAccessToken();

        $response = $this->client->asJson()
            ->acceptJson()
            ->withToken($token)
            ->patch($url, ['status_reason' => $data->status_reason]);

        if ($response->failed()) {
            return new CancelSalesOrderResult([
                'response_ok' => false,
                'status_reason' => $this->getResponseStatusReason($response),
            ]);
        }

        return new CancelSalesOrderResult([
            'response_ok' => true,
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

        return "Server responded with {$response->status()} status code.";
    }

    protected function buildCancelSalesOrderUrl(string $id): string
    {
        $resource = strtr($this->config->get('services.vs.cancel_sales_order_route'), [
            '{id}' => $id,
        ]);

        return rtrim($this->getBaseUrl(), '/').'/'.ltrim($resource, '/');
    }

    protected function getBaseUrl(): string
    {
        return $this->config->get('services.vs.url') ?? '';
    }
}
