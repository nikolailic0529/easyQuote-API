<?php

namespace App\Services\DocumentEngine;

use App\Services\DocumentEngine\Concerns\ParsesDocumentFile;
use App\Services\Exceptions\FileException;
use Illuminate\Support\Facades\Http;
use JetBrains\PhpStorm\Pure;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class GenericExcelPriceListParser implements ParsesDocumentFile
{
    const ENDPOINT = 'v1/api/dist/xls';

    protected string $filePath = '';

    protected array $options = [];

    protected LoggerInterface $logger;

    #[Pure]
    public function __construct(protected string $baseUrl,
                                protected string $clientUserName,
                                protected string $clientPassword,
                                ?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @throws FileException
     */
    public function process(): ?array
    {
        if (!file_exists($this->filePath)) {
            throw FileException::notFound($this->filePath);
        }

        $response = Http::baseUrl($this->baseUrl)
            ->withHeaders(self::HTTP_HEADERS)
            ->withBasicAuth(
                username: $this->clientUserName,
                password: $this->clientPassword,
            )
            ->acceptJson()
            ->attach('file', file_get_contents($this->filePath), basename($this->filePath))
            ->post(self::ENDPOINT, $this->options);

        if ($response->failed()) {
            $this->logger->error("HTTP request returned status code {$response->status()}.", $response->json() ?? []);

            return null;
        }

        return $response->json();
    }

    public function filePath(string $filePath): static
    {
        return tap($this, function () use ($filePath) {
            $this->filePath = $filePath;
        });
    }

    public function page(int $exactPageNumber): static
    {
        return tap($this, function () {
            //
        });
    }

    public function firstPage(int $firstPageNumber): static
    {
        return tap($this, function () use ($firstPageNumber) {
            $this->options[self::OPT_FP] = $firstPageNumber;
        });
    }

    public function lastPage(int $lastPageNumber): static
    {
        return tap($this, function () use ($lastPageNumber) {
            $this->options[self::OPT_LP] = $lastPageNumber;
        });
    }
}