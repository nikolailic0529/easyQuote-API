<?php

namespace App\Services\DocumentEngine;

use App\Contracts\CauserAware;
use App\DTO\DocumentEngine\DocumentEngineEventData;
use App\DTO\ImportableColumn\CreateColumnData;
use App\DTO\ImportableColumn\UpdateColumnData;
use App\Models\QuoteFile\ImportableColumn;
use App\Services\DocumentEngine\Models\DocumentEngineEventHandleResult;
use App\Services\ImportableColumn\ImportableColumnEntityService;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Repository as Config;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use JetBrains\PhpStorm\Pure;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DocumentEngineEventService implements CauserAware
{
    protected LoggerInterface $logger;

    const REF_HEADER_CREATED = 'document_header_created';
    const REF_HEADER_UPDATED = 'document_header_updated';
    const REF_HEADER_DELETED = 'document_header_deleted';

    protected array $acceptableEventReferences = [
        self::REF_HEADER_CREATED,
        self::REF_HEADER_UPDATED,
        self::REF_HEADER_DELETED,
    ];

    protected ?Model $causer = null;

    #[Pure]
    public function __construct(protected Config                        $config,
                                protected ConnectionInterface           $connection,
                                protected LockProvider                  $lockProvider,
                                protected ValidatorInterface            $validator,
                                protected ImportableColumnEntityService $columnEntityService,
                                ?LoggerInterface                        $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    public function processEvent(DocumentEngineEventData $event): DocumentEngineEventHandleResult
    {
        if ($this->shouldIgnoreEvent($event)) {
            return new DocumentEngineEventHandleResult(
                result: DocumentEngineEventHandleResult::IGNORED,
            );
        }

        try {
            match ($event->event_reference) {
                self::REF_HEADER_CREATED => $this->processHeaderCreatedEvent($event),
                self::REF_HEADER_UPDATED => $this->processHeaderUpdatedEvent($event),
                self::REF_HEADER_DELETED => $this->processHeaderDeletedEvent($event),
            };
        } catch (ValidationFailedException $e) {
            return new DocumentEngineEventHandleResult(
                result: DocumentEngineEventHandleResult::IGNORED,
                reason: $e->getMessage(),
            );
        }


        return new DocumentEngineEventHandleResult(
            result: DocumentEngineEventHandleResult::ACCEPTED,
        );
    }

    protected function processHeaderCreatedEvent(DocumentEngineEventData $event): void
    {
        $violations = $this->validator->validate($event->event_payload, new Constraints\Collection([
            'fields' => [
                'id' => [
                    new Constraints\NotBlank(),
                    new Constraints\Uuid(),
                    new Constraints\Callback(callback: function (mixed $value, ExecutionContextInterface $context, mixed $payload) {
                        $headerReferenceIsUnique = (bool)ImportableColumn::query()
                            ->where('de_header_reference', $value)
                            ->doesntExist();

                        if (false === $headerReferenceIsUnique) {
                            $context->buildViolation(message: 'Document header reference `{{ value }}` in use already.', parameters: ['{{ value }}' => $value])
                                ->addViolation();
                        }
                    }),
                ],
                'header_name' => [
                    new Constraints\NotBlank(),
                    new Constraints\Type(type: 'string'),
                ],
                'header_aliases' => new Constraints\All([
                    new Constraints\Collection(
                        [
                            'fields' => [
                                'id' => [
                                    new Constraints\NotBlank(),
                                    new Constraints\Uuid(),
                                ],
                                'alias_name' => [
                                    new Constraints\NotBlank(),
                                ],
                            ],
                            'allowExtraFields' => true,
                        ],
                    ),
                ]),

            ],
            'allowExtraFields' => true,
        ]));

        count($violations) && throw new ValidationFailedException($event->event_payload, $violations);

        $data = new CreateColumnData([
            'de_header_reference' => $event->event_payload['id'],
            'type' => 'text',
            'header' => $event->event_payload['header_name'],
            'is_system' => false,
            'is_temp' => false,
            'aliases' => Arr::pluck($event->event_payload['header_aliases'], 'alias_name'),
        ]);

        $this->columnEntityService
            ->setCauser($this->causer)
            ->createColumn(data: $data);
    }

    protected function processHeaderUpdatedEvent(DocumentEngineEventData $event): void
    {
        $violations = $this->validator->validate($event->event_payload, new Constraints\Collection([
            'fields' => [
                'id' => [
                    new Constraints\NotBlank(),
                    new Constraints\Uuid(),
                ],
                'header_name' => [
                    new Constraints\NotBlank(),
                    new Constraints\Type(type: 'string'),
                ],
                'header_aliases' => new Constraints\All([
                    new Constraints\Collection(
                        [
                            'fields' => [
                                'id' => [
                                    new Constraints\NotBlank(),
                                    new Constraints\Uuid(),
                                ],
                                'alias_name' => [
                                    new Constraints\NotBlank(),
                                ],
                            ],
                            'allowExtraFields' => true,
                        ],
                    ),
                ]),

            ],
            'allowExtraFields' => true,
        ]));

        count($violations) && throw new ValidationFailedException($event->event_payload, $violations);

        /** @var ImportableColumn $columnByReference */
        $columnByReference = ImportableColumn::query()
            ->where('de_header_reference', $event->event_payload['id'])
            ->sole();

        $data = new UpdateColumnData([
            'de_header_reference' => $event->event_payload['id'],
            'header' => $event->event_payload['header_name'],
            'type' => $columnByReference->type,
            'country_id' => $columnByReference->country_id,
            'order' => $columnByReference->order,
            'is_system' => (bool)$columnByReference->is_system,
            'is_temp' => (bool)$columnByReference->is_temp,
            'aliases' => Arr::pluck($event->event_payload['header_aliases'], 'alias_name'),
        ]);

        $this->columnEntityService
            ->setCauser($this->causer)
            ->updateColumn(column: $columnByReference, data: $data);
    }

    protected function processHeaderDeletedEvent(DocumentEngineEventData $event): void
    {
        $violations = $this->validator->validate($event->event_payload, new Constraints\Collection([
            'fields' => [
                'id' => [
                    new Constraints\NotBlank(),
                    new Constraints\Uuid(),
                ],

            ],
            'allowExtraFields' => true,
        ]));

        count($violations) && throw new ValidationFailedException($event->event_payload, $violations);

        /** @var ImportableColumn $columnByReference */
        $columnByReference = ImportableColumn::query()
            ->where('de_header_reference', $event->event_payload['id'])
            ->sole();

        $this->columnEntityService
            ->setCauser($this->causer)
            ->deleteColumn(column: $columnByReference);
    }

    protected function shouldIgnoreEvent(DocumentEngineEventData $event): bool
    {
        if (is_null($event->causer_reference)) {
            return true;
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        if ($event->causer_reference === $this->config->get('services.document_api.client_id')) {
            return true;
        }

        if (false === in_array($event->event_reference, $this->acceptableEventReferences, true)) {
            return true;
        }

        return false;
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, function () use ($causer) {
            $this->causer = $causer;
        });
    }
}