<?php

namespace App\Services\DocumentEngine;

use App\Contracts\LoggerAware;
use App\Services\DocumentEngine\Concerns\DocumentEngineClient;
use App\Services\DocumentEngine\Exceptions\MappingException;
use App\Services\DocumentEngine\Models\{CreateDocumentHeaderData,
    DocumentHeader,
    DocumentHeaderAlias,
    UpdateDocumentHeaderData};
use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\{Client\ConnectionException,
    Client\Factory as ClientFactory,
    Client\Pool,
    Client\RequestException,
    Client\Response};
use JetBrains\PhpStorm\Pure;
use Psr\Log\{LoggerInterface, NullLogger};
use Symfony\Component\Validator\{Exception\ValidationFailedException, Validator\ValidatorInterface};
use Webpatser\Uuid\Uuid;

class MappingClient implements DocumentEngineClient, LoggerAware
{
    const GET_HEADERS_ENDPOINT = 'v1/api/client/headers';
    const CREATE_HEADER_ENDPOINT = 'v1/api/client/headers';
    const UPDATE_HEADER_ENDPOINT = 'v1/api/client/headers/<reference>';
    const DELETE_HEADER_ENDPOINT = 'v1/api/client/headers/<reference>';
    const GET_PARTICULAR_HEADER_ENDPOINT = 'v1/api/client/headers/<reference>';
    const CREATE_HEADER_ALIAS_ENDPOINT = 'v1/api/client/header-aliases';
    const DATE_FORMAT = "Y-m-d\TH:i:s";

    protected LoggerInterface $logger;

    #[Pure]
    public function __construct(protected OauthClient $oauthClient,
                                protected Config $config,
                                protected ClientFactory $clientFactory,
                                protected ValidatorInterface $validator,
                                LoggerInterface|null $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @return DocumentHeader[]
     * @throws \App\Services\DocumentEngine\Exceptions\ClientAuthException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws ConnectionException
     */
    public function getDocumentHeaders(): array
    {
        $this->logger->debug('Fetching document headers...');

        $token = $this->oauthClient->getAccessToken();

        $response = $this->clientFactory
            ->baseUrl($this->getBaseUrl())
            ->withToken($token)
            ->get(self::GET_HEADERS_ENDPOINT);

        $json = $response->throw()->json();

        $this->logger->debug('Document headers were received.', [
            'data' => $json,
        ]);

        return array_map([$this, 'hydrateDocumentHeader'], $json);
    }

    protected function hydrateDocumentHeader(array $data): DocumentHeader
    {
        return new DocumentHeader(
            headerReference: $data['id'] ?? null,
            headerName: $data['header_name'] ?? null,
            isSystem: (bool)($data['is_system'] ?? false),
            createdAt: transform($data['created_at'] ?? null, fn(string $date) => Carbon::createFromFormat(self::DATE_FORMAT, $date)),
            updatedAt: transform($data['updated_at'] ?? null, fn(string $date) => Carbon::createFromFormat(self::DATE_FORMAT, $date)),
            headerAliases: array_map([$this, 'hydrateDocumentHeaderAlias'], $data['header_aliases'] ?? []),
        );
    }

    /**
     * Get the particular document header entity.
     *
     * @param string $headerReference
     * @return DocumentHeader
     * @throws Exceptions\ClientAuthException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws MappingException
     * @throws ConnectionException
     */
    public function getDocumentHeader(string $headerReference): DocumentHeader
    {
        $this->logger->debug('Fetching the particular document header...', [
            'headerReference' => $headerReference,
        ]);

        $token = $this->oauthClient->getAccessToken();

        $response = $this->clientFactory
            ->baseUrl($this->getBaseUrl())
            ->withToken($token)
            ->get(strtr(self::GET_PARTICULAR_HEADER_ENDPOINT, ['<reference>' => $headerReference]));

        if ($response->status() === 404) {

            $this->logger->error('Document header not found.', [
                'headerReference' => $headerReference,
            ]);

            throw MappingException::headerNotFound($headerReference);
        }

        $json = $response->throw()->json();

        $this->logger->debug('The document header was received.', [
            'headerReference' => $headerReference,
            'data' => $json,
        ]);

        return $this->hydrateDocumentHeader($json);
    }

    /**
     * @param CreateDocumentHeaderData $data
     * @return DocumentHeader
     * @throws Exceptions\ClientAuthException
     * @throws RequestException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws ConnectionException
     */
    public function createDocumentHeader(CreateDocumentHeaderData $data): DocumentHeader
    {
        $this->logger->debug('Posting a new document header...', $data->toArray());

        $violations = $this->validator->validate($data);

        count($violations) && throw new ValidationFailedException($data, $violations);

        $token = $this->oauthClient->getAccessToken();

        $response = $this->clientFactory
            ->baseUrl($this->getBaseUrl())
            ->withToken($token)
            ->post(self::CREATE_HEADER_ENDPOINT, [
                'header_name' => $data->headerName,
                'header_aliases' => $data->headerAliases,
            ]);

        $json = $response->throw()->json();

        $this->logger->debug('The document header was posted.', [
            'data' => $json,
        ]);

        return $this->hydrateDocumentHeader($json);
    }

    /**
     * @param UpdateDocumentHeaderData $data
     * @return DocumentHeader
     * @throws Exceptions\ClientAuthException
     * @throws MappingException
     * @throws RequestException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws ConnectionException
     */
    public function updateDocumentHeader(UpdateDocumentHeaderData $data): DocumentHeader
    {
        $this->logger->debug('Updating the particular document header...', $data->toArray());

        $violations = $this->validator->validate($data);

        count($violations) && throw new ValidationFailedException($data, $violations);

        // Ensure the header entity exists on the api.
        $header = $this->getDocumentHeader($data->headerReference);

        $token = $this->oauthClient->getAccessToken();

        $response = $this->clientFactory
            ->baseUrl($this->getBaseUrl())
            ->withToken($token)
            ->patch(strtr(self::UPDATE_HEADER_ENDPOINT, ['<reference>' => $header->getHeaderReference()]), [
                'header_name' => $data->headerName,
                'header_aliases' => $data->headerAliases,
            ]);

        $json = $response->throw()->json();

        $this->logger->debug('The document header was updated.', [
            'data' => $json,
        ]);

        return $this->hydrateDocumentHeader($json);
    }

    /**
     * @param string $headerReference
     * @throws Exceptions\ClientAuthException
     * @throws MappingException
     * @throws RequestException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws ConnectionException
     */
    public function deleteDocumentHeader(string $headerReference): void
    {
        $this->logger->debug('Deleting the particular document header...', [
            'headerReference' => $headerReference,
        ]);

        // Ensure the header entity exists on the api.
        $header = $this->getDocumentHeader($headerReference);

        if ($header->isSystem()) {
            throw MappingException::systemEntityConstraintsFailed($headerReference);
        }

        $token = $this->oauthClient->getAccessToken();

        $response = $this->clientFactory
            ->baseUrl($this->getBaseUrl())
            ->withToken($token)
            ->delete(strtr(self::DELETE_HEADER_ENDPOINT, ['<reference>' => $header->getHeaderReference()]));

        $response->throw();

        $this->logger->debug('The document header was deleted.');
    }

    /**
     * @param string $headerReference
     * @return DocumentHeaderAlias[]
     * @throws Exceptions\ClientAuthException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws ConnectionException
     */
    public function getAliasesOfDocumentHeader(string $headerReference): array
    {
        $this->logger->debug('Fetching alias entities of the particular document header...', [
            'headerReference' => $headerReference,
        ]);

        $token = $this->oauthClient->getAccessToken();

        $response = $this->clientFactory
            ->baseUrl($this->getBaseUrl())
            ->withToken($token)
            ->get(strtr(self::GET_PARTICULAR_HEADER_ENDPOINT, ['<reference>' => $headerReference]));

        if ($response->status() === 404) {

            $this->logger->error('Document header not found.', [
                'headerReference' => $headerReference,
            ]);

        }

        $json = $response->throw()->json();

        $this->logger->debug('Alias entities of the document header were received.', [
            'headerReference' => $headerReference,
            'data' => $json,
        ]);

        return array_map([$this, 'hydrateDocumentHeaderAlias'], $json['header_aliases'] ?? []);
    }

    protected function hydrateDocumentHeaderAlias(array $data): DocumentHeaderAlias
    {
        return new DocumentHeaderAlias(
            headerReference: $data['header_id'] ?? null,
            aliasReference: $data['id'] ?? null,
            aliasName: $data['alias_name'] ?? null,
            createdAt: transform($data['created_at'] ?? null, fn(string $date) => Carbon::createFromFormat(self::DATE_FORMAT, $date)),
            updatedAt: transform($data['updated_at'] ?? null, fn(string $date) => Carbon::createFromFormat(self::DATE_FORMAT, $date)),
        );
    }

    /**
     * @param string $headerReference
     * @param string ...$aliases
     * @return DocumentHeaderAlias[]
     * @throws Exceptions\ClientAuthException
     * @throws MappingException
     * @throws \Illuminate\Http\Client\RequestException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws ConnectionException
     * @throws \Exception
     */
    public function createAliasesForDocumentHeader(string $headerReference, string ...$aliases): array
    {
        $this->logger->debug('Creating aliases for the document header...', [
            'headerReference' => $headerReference,
        ]);

        // Ensure the header entity exists on the api.
        $header = $this->getDocumentHeader($headerReference);

        $token = $this->oauthClient->getAccessToken();

        $aliasDataDictionary = value(function () use ($header, $aliases): array {
            $data = [];

            foreach ($aliases as $aliasName) {
                $data[(string)Uuid::generate(4)] = [
                    'header_id' => $header->getHeaderReference(),
                    'alias_name' => $aliasName,
                ];
            }

            return $data;
        });

        /** @var Response[] $results */
        $results = $this->clientFactory->pool(function (Pool $pool) use ($token, $aliasDataDictionary) {

            foreach ($aliasDataDictionary as $key => $aliasPayload) {
                $pool
                    ->as($key)
                    ->baseUrl($this->getBaseUrl())
                    ->withToken($token)
                    ->post(self::CREATE_HEADER_ALIAS_ENDPOINT, $aliasPayload);
            }

        });

        $data = [];
        $createdEntities = [];

        foreach ($results as $key => $result) {

            $data[] = $entityData = $result->throw(function (Response $response, RequestException $exception) use ($key, $aliasDataDictionary) {

                $this->logger->error('Failure on create of alias for the document header.', [
                    'payload' => $aliasDataDictionary[$key],
                    'failure' => $response->json('error.message', "HTTP request returned status code {$response->status()}"),
                ]);

            })->json();

            $createdEntities[] = $this->hydrateDocumentHeaderAlias($entityData);

        }

        $this->logger->debug('Aliases for the document header created.', [
            'headerReference' => $headerReference,
            'data' => $data,
        ]);

        return $createdEntities;
    }

    protected function getBaseUrl(): string
    {
        return $this->config->get('services.document_api.url') ?? '';
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, function () use ($logger) {
            $this->logger = $logger;
        });
    }
}