<?php

namespace App\Domain\Geocoding\Integrations\AddressValidation;

use App\Domain\Geocoding\Integrations\AddressValidation\Exceptions\AddressValidationException;
use App\Domain\Geocoding\Integrations\AddressValidation\Models\ValidateAddressRequest;
use App\Domain\Geocoding\Integrations\AddressValidation\Models\ValidationResponse;
use App\Foundation\Log\Contracts\LoggerAware;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Http\Client\Factory as Client;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class AddressValidationIntegration implements ValidatesAddress, LoggerAware
{
    protected string $endpoint = 'https://addressvalidation.googleapis.com/v1:validateAddress';

    protected readonly Serializer $serializer;

    public function __construct(
        protected readonly array $config,
        protected readonly Client $client,
        protected LoggerInterface $logger = new NullLogger(),
    ) {
        $normalizers = [new BackedEnumNormalizer(), new ObjectNormalizer()];
        $encoders = [new JsonEncode()];

        $this->serializer = new Serializer($normalizers, $encoders);
    }

    public function validateAddress(ValidateAddressRequest $request): ValidationResponse
    {
        $pendingRequest = $this->client->timeout(10);
        $this->setupLoggingHandler($pendingRequest);

        $response = $pendingRequest->post($this->buildUrl(), $this->serializer->normalize($request));

        if ($response->failed()) {
            throw AddressValidationException::fromResponse($response);
        }

        return $this->serializer->denormalize($response->json(), ValidationResponse::class);
    }

    protected function setupLoggingHandler(PendingRequest $request): void
    {
        foreach (array_reverse($this->getLogFormats()) as $format) {
            $request->withMiddleware(
                Middleware::log(
                    $this->logger,
                    new MessageFormatter($format)
                )
            );
        }
    }

    protected function buildUrl(): string
    {
        return $this->endpoint.'?'.Arr::query(['key' => $this->getApiKey()]);
    }

    protected function getApiKey(): string
    {
        return $this->config['key'] ?? '';
    }

    protected function getLogFormats(): array
    {
        return $this->config['log_formats'] ?? [];
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn () => $this->logger = $logger);
    }
}
