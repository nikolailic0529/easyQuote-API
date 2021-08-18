<?php

namespace App\Services\DocumentEngine;

use App\Services\DocumentEngine\Concerns\ParsesDocumentFile;
use Illuminate\Contracts\Config\Repository as Config;
use JetBrains\PhpStorm\Pure;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ParserClientFactory
{
    protected string $filePath = '';

    protected ?int $firstPage = null;

    protected ?int $lastPage = null;

    protected ?int $page = null;

    protected LoggerInterface $logger;

    #[Pure]
    public function __construct(protected Config $config,
                                ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? (new NullLogger());
    }

    public function buildGenericExcelPriceListParser(): ParsesDocumentFile
    {
        return new GenericExcelPriceListParser(
            baseUrl: $this->getBaseUrl(),
            clientUserName: $this->getClientUsername(),
            clientPassword: $this->getClientPassword(),
            logger: $this->logger,
        );
    }

    public function buildRescuePdfPriceListParser(): ParsesDocumentFile
    {
        return new RescuePdfPriceListParser(
            baseUrl: $this->getBaseUrl(),
            clientUserName: $this->getClientUsername(),
            clientPassword: $this->getClientPassword(),
            logger: $this->logger,
        );
    }

    public function buildRescueWordPriceListParser(): ParsesDocumentFile
    {
        return new RescueWordPriceListParser(
            baseUrl: $this->getBaseUrl(),
            clientUserName: $this->getClientUsername(),
            clientPassword: $this->getClientPassword(),
            logger: $this->logger,
        );
    }

    public function buildWorldwidePdfPriceListParser(): ParsesDocumentFile
    {
        return new WorldwidePdfPriceListParser(
            baseUrl: $this->getBaseUrl(),
            clientUserName: $this->getClientUsername(),
            clientPassword: $this->getClientPassword(),
            logger: $this->logger,
        );
    }

    public function buildGenericPdfPaymentScheduleParser(): ParsesDocumentFile
    {
        return new GenericPdfPaymentScheduleParser(
            baseUrl: $this->getBaseUrl(),
            clientUserName: $this->getClientUsername(),
            clientPassword: $this->getClientPassword(),
            logger: $this->logger,
        );
    }

    protected function getBaseUrl(): string
    {
        return $this->config->get('services.document_api.url') ?? '';
    }

    protected function getClientUsername(): string
    {
        return $this->config->get('services.document_api.client_username') ?? '';
    }

    protected function getClientPassword(): string
    {
        return $this->config->get('services.document_api.client_password') ?? '';
    }
}
