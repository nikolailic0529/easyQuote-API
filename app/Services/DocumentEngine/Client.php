<?php

namespace App\Services\DocumentEngine;

use Illuminate\Support\Facades\Http;
use App\Services\Exceptions\FileNotFound;
use App\Services\Dummy\Logger;
use Psr\Log\LoggerInterface;

abstract class Client
{
    /** @var string */
    protected $filePath;

    /** @var integer */
    protected $firstPage;

    /** @var integer */
    protected $lastPage;

    /** @var integer */
    protected $page;

    /** @var integer */
    protected $tries;

    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $logger ??= (new Logger);

        $this->logger = $logger;
    }

    abstract protected function endpoint();

    final public function process()
    {
        if (!file_exists($this->filePath)) {
            throw FileNotFound::filePath($this->filePath);
        }

        $response = Http::baseUrl($this->baseUrl())
            ->withHeaders([
                'Accept-Encoding' => 'gzip',
            ])
            ->acceptJson()
            ->attach('file', file_get_contents($this->filePath), basename($this->filePath))
            ->retry($this->tries ?? 2, 100)
            ->post($this->endpoint(), $this->buildParameters());

        if ($response->failed()) {
            $this->logger->error("HTTP request returned status code {$response->status()}.", $response->json() ?? []);

            return null;
        }

        return $response->json();
    }

    final protected function buildParameters(): array
    {
        $parameters = [];

        if (isset($this->page)) {
            $parameters['page'] = $this->page;
        }

        if (isset($this->firstPage)) {
            $parameters['first_page'] = $this->firstPage;
        }

        if (isset($this->lastPage)) {
            $parameters['last_page'] = $this->lastPage;
        }

        return $parameters;
    }

    /** @return $this */
    final public function retry(int $tries)
    {
        $this->tries = $tries;

        return $this;
    }

    /** @return $this */
    final public function filePath(string $filePath)
    {
        $this->filePath = $filePath;

        return $this;
    }

    /** @return $this */
    final public function page(int $page)
    {
        $this->page = $page;

        return $this;
    }

    /** @return $this */
    final public function firstPage(int $firstPage)
    {
        $this->firstPage = $firstPage;

        return $this;
    }

    /** @return $this */
    final public function lastPage(int $lastPage)
    {
        $this->lastPage = $lastPage;

        return $this;
    }

    final protected function baseUrl()
    {
        return config('services.document_api.url');
    }
}