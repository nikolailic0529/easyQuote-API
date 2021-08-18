<?php

namespace Tests\Unit;

use App\Services\DocumentEngine\Exceptions\MappingException;
use App\Services\DocumentEngine\MappingClient;
use App\Services\DocumentEngine\Models\CreateDocumentHeaderData;
use App\Services\DocumentEngine\Models\DocumentHeader;
use App\Services\DocumentEngine\Models\DocumentHeaderAlias;
use App\Services\DocumentEngine\Models\UpdateDocumentHeaderData;
use App\Services\DocumentEngine\OauthClient;
use DateTimeInterface;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Client\Factory as ClientFactory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Class DocumentEngineMappingClientTest
 *
 * @group document-engine-api
 */
class DocumentEngineMappingClientTest extends TestCase
{
    /**
     * Test the client gets document headers from the endpoint.
     *
     * @return void
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function test_it_can_get_document_headers()
    {
        $baseUrl = 'http://localhost:1337';

        /** @var ClientFactory $clientFactory */
        $clientFactory = $this->app['document-engine-api::client'];

        $clientFactory->fake([
            OauthClient::ENDPOINT => Http::response(body: [
                'access_token' => $accessTokenFromResponse = Str::random(125),
                'expires_in' => $expiresInFromResponse = now()->addYear()->diffInSeconds(now()),
            ]),
            MappingClient::GET_HEADERS_ENDPOINT => Http::response(
                body: [
                    [
                        "id" => "037ccfaa-1f4c-4cf5-8ce0-881d9ecd8c99",
                        "header_name" => "Searchable",
                        "is_system" => 1,
                        "created_at" => "2021-07-22T10:54:08",
                        "updated_at" => "2021-07-22T10:54:08",
                    ],
                    [
                        "id" => "0fb6c989-2f8b-4c38-916f-0c5efe2b1920",
                        "header_name" => "Serial No.",
                        "is_system" => 1,
                        "created_at" => "2021-07-22T10:54:08",
                        "updated_at" => "2021-07-22T10:54:08",
                    ],
                    [
                        "id" => "18161865-8a29-4dad-98fd-271a47f34cc8",
                        "header_name" => "System Handle",
                        "is_system" => 1,
                        "created_at" => "2021-07-22T10:54:08",
                        "updated_at" => "2021-07-22T10:54:08",
                    ],
                    [
                        "id" => "1ea417fd-b87b-4a79-a354-f52b05036675",
                        "header_name" => "Coverage to",
                        "is_system" => 1,
                        "created_at" => "2021-07-22T10:54:08",
                        "updated_at" => "2021-07-22T10:54:08",
                    ],
                    [
                        "id" => "568b342a-3a02-4438-8fc8-52cd9794d330",
                        "header_name" => "Product No.",
                        "is_system" => 1,
                        "created_at" => "2021-07-22T10:54:08",
                        "updated_at" => "2021-07-22T10:54:08",
                    ],
                    [
                        "id" => "650bc9c3-994a-4cf9-9990-31e3b852f1ee",
                        "header_name" => "Pricing Document",
                        "is_system" => 1,
                        "created_at" => "2021-07-22T10:54:08",
                        "updated_at" => "2021-07-22T10:54:08",
                    ],
                    [
                        "id" => "8165f86b-736f-4a73-b460-7a14c10b959e",
                        "header_name" => "Quantity",
                        "is_system" => 1,
                        "created_at" => "2021-07-22T10:54:08",
                        "updated_at" => "2021-07-22T10:54:08",
                    ],
                    [
                        "id" => "8e1cfd31-52a6-4e50-844e-67c75829e4db",
                        "header_name" => "Description",
                        "is_system" => 1,
                        "created_at" => "2021-07-22T10:54:08",
                        "updated_at" => "2021-07-22T10:54:08",
                    ],
                    [
                        "id" => "94d87af5-e8c1-42fb-8aa7-b6db3a539fe5",
                        "header_name" => "Price",
                        "is_system" => 1,
                        "created_at" => "2021-07-22T10:54:08",
                        "updated_at" => "2021-07-22T10:54:08",
                    ],
                    [
                        "id" => "f0520abb-c251-4568-8a7b-207599f7ee5b",
                        "header_name" => "Coverage from",
                        "is_system" => 1,
                        "created_at" => "2021-07-22T10:54:08",
                        "updated_at" => "2021-07-22T10:54:08",
                    ],
                ]
            ),
        ]);

        $this->app->instance('document-engine-api::client', $clientFactory);

        $this->partialMock(Config::class, function (\Mockery\MockInterface $mock) use ($baseUrl) {
            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.url')
                ->andReturn($baseUrl);

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_id')
                ->andReturn(Str::uuid());

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_secret')
                ->andReturn(Str::random(40));
        });

        /** @var MappingClient $mappingClient */
        $mappingClient = $this->app[MappingClient::class];

        $headers = $mappingClient->getDocumentHeaders();

        $this->assertIsArray($headers);
        $this->assertNotEmpty($headers);

        foreach ($headers as $header) {
            $this->assertInstanceOf(DocumentHeader::class, $header);

            $this->assertIsString($header->getHeaderReference());
            $this->assertNotEmpty($header->getHeaderReference());
            $this->assertIsString($header->getHeaderName());
            $this->assertNotEmpty($header->getHeaderName());
            $this->assertIsBool($header->isSystem());
            $this->assertInstanceOf(DateTimeInterface::class, $header->getCreatedAt());
            $this->assertInstanceOf(DateTimeInterface::class, $header->getUpdatedAt());
        }
    }

    /**
     * Test the client gets aliases of the particular header reference.
     *
     * @return void
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function test_it_can_get_aliases_of_a_document_header()
    {
        $baseUrl = 'http://localhost:1337';

        /** @var ClientFactory $clientFactory */
        $clientFactory = $this->app['document-engine-api::client'];

        $headerReference = "568b342a-3a02-4438-8fc8-52cd9794d330";

        $clientFactory->fake([
            OauthClient::ENDPOINT => Http::response(body: [
                'access_token' => $accessTokenFromResponse = Str::random(125),
                'expires_in' => $expiresInFromResponse = now()->addYear()->diffInSeconds(now()),
            ]),
            strtr(MappingClient::GET_PARTICULAR_HEADER_ENDPOINT, ['<reference>' => '*']) => Http::response(
                body: [
                    'id' => $headerReference,
                    'header_name' => "Product No.",
                    'created_at' => "2021-07-22T10:54:08",
                    'updated_at' => "2021-07-22T10:54:08",
                    'header_aliases' => [
                        [
                            "id" => "1ac29e1a-02fd-4cd2-9c7d-4cee1fa844bd",
                            "header_id" => $headerReference,
                            "alias_name" => "numéro de produit",
                            "created_at" => "2021-07-22T12:13:53",
                            "updated_at" => "2021-07-22T12:13:53",
                        ],
                        [
                            "id" => "4496d1da-6add-435f-9fc3-503382223073",
                            "header_id" => $headerReference,
                            "alias_name" => "produktnummer",
                            "created_at" => "2021-07-22T12:13:53",
                            "updated_at" => "2021-07-22T12:13:53",
                        ],
                        [
                            "id" => "5e0b8af8-281b-4331-b211-b0a143d00da0",
                            "header_id" => $headerReference,
                            "alias_name" => "product_number",
                            "created_at" => "2021-07-22T12:13:53",
                            "updated_at" => "2021-07-22T12:13:53",
                        ],
                    ],
                ]
            ),
        ]);

        $this->app->instance('document-engine-api::client', $clientFactory);

        $this->partialMock(Config::class, function (\Mockery\MockInterface $mock) use ($baseUrl) {
            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.url')
                ->andReturn($baseUrl);

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_id')
                ->andReturn(Str::uuid());

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_secret')
                ->andReturn(Str::random(40));
        });

        /** @var MappingClient $mappingClient */
        $mappingClient = $this->app[MappingClient::class];

        $aliases = $mappingClient->getAliasesOfDocumentHeader($headerReference);

        $this->assertIsArray($aliases);
        $this->assertNotEmpty($aliases);

        foreach ($aliases as $alias) {

            $this->assertInstanceOf(DocumentHeaderAlias::class, $alias);

            $this->assertSame($headerReference, $alias->getHeaderReference());
            $this->assertTrue(Str::isUuid($alias->getAliasReference()));

            $this->assertIsString($alias->getAliasName());
            $this->assertNotEmpty($alias->getAliasName());

            $this->assertInstanceOf(DateTimeInterface::class, $alias->getCreatedAt());
            $this->assertInstanceOf(DateTimeInterface::class, $alias->getUpdatedAt());

        }
    }

    /**
     * Test the client gets a particular document header.
     *
     * @return void
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function test_it_can_get_particular_document_header()
    {
        $baseUrl = 'http://localhost:1337';

        /** @var ClientFactory $clientFactory */
        $clientFactory = $this->app['document-engine-api::client'];

        $headerReference = "568b342a-3a02-4438-8fc8-52cd9794d330";

        $clientFactory->fake([
            OauthClient::ENDPOINT => Http::response(body: [
                'access_token' => $accessTokenFromResponse = Str::random(125),
                'expires_in' => $expiresInFromResponse = now()->addYear()->diffInSeconds(now()),
            ]),
            strtr(MappingClient::GET_PARTICULAR_HEADER_ENDPOINT, ['<reference>' => '*']) => Http::response(
                body: [
                    'id' => $headerReference,
                    'header_name' => "Product No.",
                    'is_system' => 1,
                    'created_at' => "2021-07-22T10:54:08",
                    'updated_at' => "2021-07-22T10:54:08",
                    'header_aliases' => [
                        [
                            "id" => "1ac29e1a-02fd-4cd2-9c7d-4cee1fa844bd",
                            "header_id" => $headerReference,
                            "alias_name" => "numéro de produit",
                            "created_at" => "2021-07-22T12:13:53",
                            "updated_at" => "2021-07-22T12:13:53",
                        ],
                        [
                            "id" => "4496d1da-6add-435f-9fc3-503382223073",
                            "header_id" => $headerReference,
                            "alias_name" => "produktnummer",
                            "created_at" => "2021-07-22T12:13:53",
                            "updated_at" => "2021-07-22T12:13:53",
                        ],
                        [
                            "id" => "5e0b8af8-281b-4331-b211-b0a143d00da0",
                            "header_id" => $headerReference,
                            "alias_name" => "product_number",
                            "created_at" => "2021-07-22T12:13:53",
                            "updated_at" => "2021-07-22T12:13:53",
                        ],
                    ],
                ]
            ),
        ]);

        $this->app->instance('document-engine-api::client', $clientFactory);

        $this->partialMock(Config::class, function (\Mockery\MockInterface $mock) use ($baseUrl) {
            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.url')
                ->andReturn($baseUrl);

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_id')
                ->andReturn(Str::uuid());

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_secret')
                ->andReturn(Str::random(40));
        });

        /** @var MappingClient $mappingClient */
        $mappingClient = $this->app[MappingClient::class];

        $documentHeader = $mappingClient->getDocumentHeader($headerReference);

        $this->assertInstanceOf(DocumentHeader::class, $documentHeader);
        $this->assertSame("568b342a-3a02-4438-8fc8-52cd9794d330", $documentHeader->getHeaderReference());
        $this->assertSame("Product No.", $documentHeader->getHeaderName());
        $this->assertTrue($documentHeader->isSystem());
        $this->assertInstanceOf(DateTimeInterface::class, $documentHeader->getCreatedAt());
        $this->assertInstanceOf(DateTimeInterface::class, $documentHeader->getUpdatedAt());
    }

    /**
     * Test the client creates aliases for a particular document header.
     *
     * @return void
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function test_it_can_create_aliases_for_document_header()
    {
        $baseUrl = 'http://localhost:1337';

        /** @var ClientFactory $clientFactory */
        $clientFactory = $this->app['document-engine-api::client'];

        $headerReference = "568b342a-3a02-4438-8fc8-52cd9794d330";

        $clientFactory->fake([
            OauthClient::ENDPOINT => Http::response(body: [
                'access_token' => $accessTokenFromResponse = Str::random(125),
                'expires_in' => $expiresInFromResponse = now()->addYear()->diffInSeconds(now()),
            ]),
            strtr(MappingClient::GET_PARTICULAR_HEADER_ENDPOINT, ['<reference>' => '*']) => Http::response(
                body: [
                    'id' => $headerReference,
                    'header_name' => "Product No.",
                    'is_system' => 1,
                    'created_at' => "2021-07-22T10:54:08",
                    'updated_at' => "2021-07-22T10:54:08",
                    'header_aliases' => [
                        [
                            "id" => "1ac29e1a-02fd-4cd2-9c7d-4cee1fa844bd",
                            "header_id" => $headerReference,
                            "alias_name" => "numéro de produit",
                            "created_at" => "2021-07-22T12:13:53",
                            "updated_at" => "2021-07-22T12:13:53",
                        ],
                        [
                            "id" => "4496d1da-6add-435f-9fc3-503382223073",
                            "header_id" => $headerReference,
                            "alias_name" => "produktnummer",
                            "created_at" => "2021-07-22T12:13:53",
                            "updated_at" => "2021-07-22T12:13:53",
                        ],
                        [
                            "id" => "5e0b8af8-281b-4331-b211-b0a143d00da0",
                            "header_id" => $headerReference,
                            "alias_name" => "product_number",
                            "created_at" => "2021-07-22T12:13:53",
                            "updated_at" => "2021-07-22T12:13:53",
                        ],
                    ],
                ]
            ),
            MappingClient::CREATE_HEADER_ALIAS_ENDPOINT => $clientFactory->sequence()
                ->push([
                    'id' => (string)Str::uuid(),
                    'header_id' => $headerReference,
                    'alias_name' => "part_id",
                    'created_at' => "2021-07-22T12:13:53",
                    'updated_at' => "2021-07-22T12:13:53",
                ])
                ->push([
                    'id' => (string)Str::uuid(),
                    'header_id' => $headerReference,
                    'alias_name' => "product_nu",
                    'created_at' => "2021-07-22T12:13:53",
                    'updated_at' => "2021-07-22T12:13:53",
                ]),
        ]);

        $this->app->instance('document-engine-api::client', $clientFactory);

        $this->partialMock(Config::class, function (\Mockery\MockInterface $mock) use ($baseUrl) {
            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.url')
                ->andReturn($baseUrl);

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_id')
                ->andReturn(Str::uuid());

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_secret')
                ->andReturn(Str::random(40));
        });

        /** @var MappingClient $mappingClient */
        $mappingClient = $this->app[MappingClient::class];

        $results = $mappingClient->createAliasesForDocumentHeader($headerReference, 'part_id', 'product_nu');

        $this->assertNotEmpty($results);
        $this->assertCount(2, $results);

        foreach ($results as $result) {
            $this->assertInstanceOf(DocumentHeaderAlias::class, $result);

            $this->assertTrue(Str::isUuid($result->getAliasReference()));

            $this->assertSame($headerReference, $result->getHeaderReference());

            $this->assertNotEmpty($result->getAliasName());
            $this->assertIsString($result->getAliasName());

            $this->assertInstanceOf(DateTimeInterface::class, $result->getCreatedAt());
            $this->assertInstanceOf(DateTimeInterface::class, $result->getUpdatedAt());
        }
    }

    /**
     * Test the client creates a new document header with aliases.
     *
     * @return void
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function test_it_can_create_a_new_document_header()
    {
        $baseUrl = 'http://localhost:1337';

        /** @var ClientFactory $clientFactory */
        $clientFactory = $this->app['document-engine-api::client'];

        $headerReference = "650bc9c3-994a-4cf9-9990-31e3b852f1ee";

        $headerAliases = [
            "description du produit",
            "product description",
        ];

        $clientFactory->fake([
            OauthClient::ENDPOINT => Http::response(body: [
                'access_token' => $accessTokenFromResponse = Str::random(125),
                'expires_in' => $expiresInFromResponse = now()->addYear()->diffInSeconds(now()),
            ]),
            MappingClient::CREATE_HEADER_ENDPOINT => $clientFactory->sequence()
                ->push([
                    'id' => $headerReference,
                    'header_name' => "Pricing Document",
                    'is_system' => 0,
                    'created_at' => now()->format(MappingClient::DATE_FORMAT),
                    'updated_at' => now()->format(MappingClient::DATE_FORMAT),
                    'header_aliases' => [
                        [
                            "id" => (string)Str::uuid(),
                            "header_id" => $headerReference,
                            "alias_name" => "description du produit",
                            "created_at" => now()->format(MappingClient::DATE_FORMAT),
                            "updated_at" => now()->format(MappingClient::DATE_FORMAT),
                        ],
                        [
                            "id" => (string)Str::uuid(),
                            "header_id" => $headerReference,
                            "alias_name" => "product description",
                            "created_at" => now()->format(MappingClient::DATE_FORMAT),
                            "updated_at" => now()->format(MappingClient::DATE_FORMAT),
                        ],
                    ],
                ]),
        ]);

        $this->app->instance('document-engine-api::client', $clientFactory);

        $this->partialMock(Config::class, function (\Mockery\MockInterface $mock) use ($baseUrl) {
            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.url')
                ->andReturn($baseUrl);

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_id')
                ->andReturn(Str::uuid());

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_secret')
                ->andReturn(Str::random(40));
        });

        /** @var MappingClient $mappingClient */
        $mappingClient = $this->app[MappingClient::class];

        $result = $mappingClient->createDocumentHeader(new CreateDocumentHeaderData([
            'headerName' => "Pricing Document",
            'headerAliases' => $headerAliases,
        ]));

        $this->assertCount(count($headerAliases), $result->getHeaderAliases());

        $headerAliasNamesFromResult = array_map(fn(DocumentHeaderAlias $alias) => $alias->getAliasName(), $result->getHeaderAliases());

        foreach ($headerAliases as $alias) {

            $this->assertContains($alias, $headerAliasNamesFromResult);

        }
    }

    /**
     * Test the client updates an existing document header with aliases.
     *
     * @return void
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function test_it_can_update_an_existing_header()
    {
        $baseUrl = 'http://localhost:1337';

        /** @var ClientFactory $clientFactory */
        $clientFactory = $this->app['document-engine-api::client'];

        $headerReference = "650bc9c3-994a-4cf9-9990-31e3b852f1ee";

        $headerAliases = [
            "description du produit",
            "product description",
        ];

        $clientFactory->fake([
            OauthClient::ENDPOINT => Http::response(body: [
                'access_token' => $accessTokenFromResponse = Str::random(125),
                'expires_in' => $expiresInFromResponse = now()->addYear()->diffInSeconds(now()),
            ]),
            'v1/api/client/headers/*' => $clientFactory->sequence()
                ->push([
                    'id' => $headerReference,
                    'header_name' => "Pricing Document",
                    'is_system' => 0,
                    'created_at' => now()->format(MappingClient::DATE_FORMAT),
                    'updated_at' => now()->format(MappingClient::DATE_FORMAT),
                    'header_aliases' => [
                        [
                            "id" => (string)Str::uuid(),
                            "header_id" => $headerReference,
                            "alias_name" => "description",
                            "created_at" => now()->format(MappingClient::DATE_FORMAT),
                            "updated_at" => now()->format(MappingClient::DATE_FORMAT),
                        ],
                    ],
                ])
                ->push([
                    'id' => $headerReference,
                    'header_name' => "Pricing Document",
                    'is_system' => 0,
                    'created_at' => now()->format(MappingClient::DATE_FORMAT),
                    'updated_at' => now()->format(MappingClient::DATE_FORMAT),
                    'header_aliases' => [
                        [
                            "id" => (string)Str::uuid(),
                            "header_id" => $headerReference,
                            "alias_name" => "description du produit",
                            "created_at" => now()->format(MappingClient::DATE_FORMAT),
                            "updated_at" => now()->format(MappingClient::DATE_FORMAT),
                        ],
                        [
                            "id" => (string)Str::uuid(),
                            "header_id" => $headerReference,
                            "alias_name" => "product description",
                            "created_at" => now()->format(MappingClient::DATE_FORMAT),
                            "updated_at" => now()->format(MappingClient::DATE_FORMAT),
                        ],
                    ],
                ]),
        ]);

        $this->app->instance('document-engine-api::client', $clientFactory);

        $this->partialMock(Config::class, function (\Mockery\MockInterface $mock) use ($baseUrl) {
            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.url')
                ->andReturn($baseUrl);

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_id')
                ->andReturn(Str::uuid());

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_secret')
                ->andReturn(Str::random(40));
        });

        /** @var MappingClient $mappingClient */
        $mappingClient = $this->app[MappingClient::class];

        $result = $mappingClient->updateDocumentHeader(new UpdateDocumentHeaderData([
            'headerReference' => $headerReference,
            'headerName' => "Pricing Document",
            'headerAliases' => $headerAliases,
        ]));

        $this->assertCount(count($headerAliases), $result->getHeaderAliases());

        $headerAliasNamesFromResult = array_map(fn(DocumentHeaderAlias $alias) => $alias->getAliasName(), $result->getHeaderAliases());

        foreach ($headerAliases as $alias) {

            $this->assertContains($alias, $headerAliasNamesFromResult);

        }
    }

    /**
     * Test the client deletes an existing document header on api.
     *
     * @return void
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function test_it_can_delete_an_existing_header()
    {
        $baseUrl = 'http://localhost:1337';

        /** @var ClientFactory $clientFactory */
        $clientFactory = $this->app['document-engine-api::client'];

        $headerReference = "650bc9c3-994a-4cf9-9990-31e3b852f1ee";

        $clientFactory->fake([
            OauthClient::ENDPOINT => Http::response(body: [
                'access_token' => $accessTokenFromResponse = Str::random(125),
                'expires_in' => $expiresInFromResponse = now()->addYear()->diffInSeconds(now()),
            ]),
            'v1/api/client/headers/*' => $clientFactory->sequence()
                ->push([
                    'id' => $headerReference,
                    'header_name' => "Pricing Document",
                    'is_system' => 0,
                    'created_at' => now()->format(MappingClient::DATE_FORMAT),
                    'updated_at' => now()->format(MappingClient::DATE_FORMAT),
                    'header_aliases' => [
                        [
                            "id" => (string)Str::uuid(),
                            "header_id" => $headerReference,
                            "alias_name" => "description",
                            "created_at" => now()->format(MappingClient::DATE_FORMAT),
                            "updated_at" => now()->format(MappingClient::DATE_FORMAT),
                        ],
                    ],
                ])
                ->push(status: 204),
        ]);

        $this->app->instance('document-engine-api::client', $clientFactory);

        $this->partialMock(Config::class, function (\Mockery\MockInterface $mock) use ($baseUrl) {
            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.url')
                ->andReturn($baseUrl);

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_id')
                ->andReturn(Str::uuid());

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_secret')
                ->andReturn(Str::random(40));
        });

        /** @var MappingClient $mappingClient */
        $mappingClient = $this->app[MappingClient::class];

        $mappingClient->deleteDocumentHeader($headerReference);
    }

    /**
     * Test the client prevents deletion of a system defined document header.
     *
     * @return void
     */
    public function test_it_prevents_delete_a_system_header()
    {
        $baseUrl = 'http://localhost:1337';

        /** @var ClientFactory $clientFactory */
        $clientFactory = $this->app['document-engine-api::client'];

        $headerReference = "650bc9c3-994a-4cf9-9990-31e3b852f1ee";

        $clientFactory->fake([
            OauthClient::ENDPOINT => Http::response(body: [
                'access_token' => $accessTokenFromResponse = Str::random(125),
                'expires_in' => $expiresInFromResponse = now()->addYear()->diffInSeconds(now()),
            ]),
            'v1/api/client/headers/*' => $clientFactory->sequence()
                ->push([
                    'id' => $headerReference,
                    'header_name' => "Pricing Document",
                    'is_system' => 1,
                    'created_at' => now()->format(MappingClient::DATE_FORMAT),
                    'updated_at' => now()->format(MappingClient::DATE_FORMAT),
                    'header_aliases' => [
                        [
                            "id" => (string)Str::uuid(),
                            "header_id" => $headerReference,
                            "alias_name" => "description",
                            "created_at" => now()->format(MappingClient::DATE_FORMAT),
                            "updated_at" => now()->format(MappingClient::DATE_FORMAT),
                        ],
                    ],
                ])
                ->push(status: 204),
        ]);

        $this->app->instance('document-engine-api::client', $clientFactory);

        $this->partialMock(Config::class, function (\Mockery\MockInterface $mock) use ($baseUrl) {
            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.url')
                ->andReturn($baseUrl);

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_id')
                ->andReturn(Str::uuid());

            $mock->shouldReceive('get')
                ->withSomeOfArgs('services.document_api.client_secret')
                ->andReturn(Str::random(40));
        });

        /** @var MappingClient $mappingClient */
        $mappingClient = $this->app[MappingClient::class];

        $this->expectException(MappingException::class);
        $this->expectExceptionMessageMatches('/Unable to perform an action on the system defined entity with reference.+/');

        $mappingClient->deleteDocumentHeader($headerReference);
    }
}
