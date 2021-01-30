<?php

namespace App\Services\DocumentEngine;

use App\Services\Dummy\Logger;
use App\Services\Exceptions\FileNotFound;
use Illuminate\Support\Facades\Http;
use Psr\Log\LoggerInterface;

abstract class Client
{
    protected string $filePath = '';

    protected ?int $firstPage = null;

    protected ?int $lastPage = null;

    protected ?int $page = null;

    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $logger ??= (new Logger);

        $this->logger = $logger;
    }

    abstract protected function endpoint(): string;

    /**
     * @return array|mixed|null
     * @throws FileNotFound
     */
    final public function process(): ?array
    {
        if (!file_exists($this->filePath)) {
            throw FileNotFound::filePath($this->filePath);
        }

        $response = Http::baseUrl($this->getBaseUrl())
            ->withHeaders([
                'Accept-Encoding' => 'gzip',
            ])
            ->withBasicAuth(
                $this->getClientUsername(),
                $this->getClientPassword()
            )
            ->acceptJson()
            ->attach('file', file_get_contents($this->filePath), basename($this->filePath))
            ->post($this->endpoint(), $this->buildParameters());

        if ($response->failed()) {
            $this->logger->error("HTTP request returned status code {$response->status()}.", $response->json() ?? []);

            return null;
        }

        return $response->json();
    }

    final protected function buildParameters(): array
    {
        return array_filter([
            'page' => $this->page,
            'first_page' => $this->firstPage,
            'last_page' => $this->lastPage,
        ], fn($value) => !is_null($value));
    }

    /**
     * @param int $tries
     * @return $this
     */
    final public function retry(int $tries): Client
    {
        $this->tries = $tries;

        return $this;
    }

    /**
     * @param string $filePath
     * @return $this
     */
    final public function filePath(string $filePath): Client
    {
        $this->filePath = $filePath;

        return $this;
    }

    /**
     * @param int $page
     * @return $this
     */
    final public function page(int $page): Client
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @param int $firstPage
     * @return $this
     */
    final public function firstPage(int $firstPage): Client
    {
        $this->firstPage = $firstPage;

        return $this;
    }

    /**
     * @param int $lastPage
     * @return $this
     */
    final public function lastPage(int $lastPage): Client
    {
        $this->lastPage = $lastPage;

        return $this;
    }

    protected function getBaseUrl(): string
    {
        return config('services.document_api.url') ?? '';
    }

    protected function getClientUsername(): string
    {
        return config('services.document_api.client_username') ?? '';
    }

    protected function getClientPassword(): string
    {
        return config('services.document_api.client_password') ?? '';
    }
}
