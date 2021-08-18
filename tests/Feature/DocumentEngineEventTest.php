<?php

namespace Tests\Feature;

use App\Models\QuoteFile\ImportableColumn;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Webpatser\Uuid\Uuid;

class DocumentEngineEventTest extends TestCase
{
    use DatabaseTransactions;

    public function testCanSendDocumentEngineEventWithDocumentHeaderCreatedEventReferenceUsingClientCredentials()
    {
        $this->authenticateAsClient();

        $this->postJson('api/document-engine/events', [
            'event_reference' => 'document_header_created',
            'causer_reference' => (string)Uuid::generate(4),
            'event_payload' => [
                'id' => $headerReference = (string)Uuid::generate(4),
                'header_name' => 'NEW HEADER',
                'header_aliases' => [
                    [
                        'id' => (string)Uuid::generate(4),
                        'alias_name' => "NEW HEADER ALIAS",
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                    ]
                ],
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ])
//            ->dump()
            ->assertStatus(202)
            ->assertJsonStructure([
                'result'
            ]);

        // Assert that the header reference in use already,
        // and the event payload is being ignored.
        $this->postJson('api/document-engine/events', [
            'event_reference' => 'document_header_created',
            'causer_reference' => (string)Uuid::generate(4),
            'event_payload' => [
                'id' => $headerReference,
                'header_name' => 'NEW HEADER',
                'header_aliases' => [
                    [
                        'id' => (string)Uuid::generate(4),
                        'alias_name' => "NEW HEADER ALIAS",
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                    ]
                ],
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ])
//            ->dump()
            ->assertStatus(422)
            ->assertJsonStructure([
                'result',
                'reason',
            ])
            ->assertJson([
                'result' => 'ignored'
            ]);

        $response = $this->getJson('api/document-engine/document-headers/linked')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'de_header_reference',
                    'header',
                    'name',
                    'created_at',
                    'updated_at',
                    'aliases' => [
                        '*' => [
                            'id', 'alias', 'created_at', 'updated_at',
                        ]
                    ]
                ]
            ]);

        $this->assertContains($headerReference, $response->json('*.de_header_reference'));

        $headerFromResponse = Arr::first($response->json(), function (array $header) use ($headerReference) {
            return $header['de_header_reference'] === $headerReference;
        });

        $this->assertSame('NEW HEADER', $headerFromResponse['header']);
    }

    public function testCanSendDocumentEngineEventWithDocumentHeaderUpdatedEventReferenceUsingClientCredentials()
    {
        $this->authenticateAsClient();

        /** @var ImportableColumn $importableColumn */
        $importableColumn = factory(ImportableColumn::class)->create([
           'de_header_reference' =>  (string)Uuid::generate(4),
        ]);

        $importableColumn->aliases()->create([
            'alias' => 'existing header alias'
        ]);

        $this->postJson('api/document-engine/events', [
            'event_reference' => 'document_header_updated',
            'causer_reference' => (string)Uuid::generate(4),
            'event_payload' => [
                'id' => $importableColumn->de_header_reference,
                'header_name' => 'UPDATED HEADER',
                'header_aliases' => [
                    [
                        'id' => (string)Uuid::generate(4),
                        'alias_name' => "UPDATED HEADER ALIAS",
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                    ]
                ],
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ])
//            ->dump()
            ->assertStatus(202)
            ->assertJsonStructure([
                'result'
            ]);

        $response = $this->getJson('api/document-engine/document-headers/linked')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'de_header_reference',
                    'header',
                    'name',
                    'created_at',
                    'updated_at',
                    'aliases' => [
                        '*' => [
                            'id', 'alias', 'created_at', 'updated_at',
                        ]
                    ]
                ]
            ]);

        $this->assertContains($importableColumn->de_header_reference, $response->json('*.de_header_reference'));

        $headerFromResponse = Arr::first($response->json(), function (array $header) use ($importableColumn) {
            return $header['de_header_reference'] === $importableColumn->de_header_reference;
        });

        $this->assertNotEmpty($headerFromResponse);

        $this->assertSame('UPDATED HEADER', $headerFromResponse['header']);
        $this->assertCount(1, $headerFromResponse['aliases']);
        $this->assertSame('UPDATED HEADER ALIAS', $headerFromResponse['aliases'][0]['alias']);
    }

    public function testCanSendDocumentEngineEventWithDocumentHeaderDeletedEventReferenceUsingClientCredentials()
    {
        $this->authenticateAsClient();

        /** @var ImportableColumn $importableColumn */
        $importableColumn = factory(ImportableColumn::class)->create([
            'de_header_reference' =>  (string)Uuid::generate(4),
        ]);

        $this->postJson('api/document-engine/events', [
            'event_reference' => 'document_header_deleted',
            'causer_reference' => (string)Uuid::generate(4),
            'event_payload' => [
                'id' => $importableColumn->de_header_reference,
                'header_name' => 'UPDATED HEADER',
                'header_aliases' => [
                    [
                        'id' => (string)Uuid::generate(4),
                        'alias_name' => "UPDATED HEADER ALIAS",
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                    ]
                ],
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ],
        ])
//            ->dump()
            ->assertStatus(202)
            ->assertJsonStructure([
                'result'
            ]);

        $response = $this->getJson('api/document-engine/document-headers/linked')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'de_header_reference',
                    'header',
                    'name',
                    'created_at',
                    'updated_at',
                    'aliases' => [
                        '*' => [
                            'id', 'alias', 'created_at', 'updated_at',
                        ]
                    ]
                ]
            ]);

        $this->assertNotContains($importableColumn->de_header_reference, $response->json('*.de_header_reference'));
    }
}