<?php

namespace App\Domain\QuoteFile\Listeners;

use App\Domain\Authentication\Contracts\WithCauserEntity;
use App\Domain\DocumentEngine\Exceptions\DocumentEngineClientException;
use App\Domain\DocumentEngine\MappingClient;
use App\Domain\DocumentEngine\Models\CreateDocumentHeaderData;
use App\Domain\DocumentEngine\Models\UpdateDocumentHeaderData;
use App\Domain\QuoteFile\Contracts\WithImportableColumnEntity;
use App\Domain\QuoteFile\Events\ImportableColumn\ImportableColumnCreated;
use App\Domain\QuoteFile\Events\ImportableColumn\ImportableColumnDeleted;
use App\Domain\QuoteFile\Events\ImportableColumn\ImportableColumnUpdated;
use App\Domain\QuoteFile\Models\ImportableColumn;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\ConnectionException;
use Laravel\Passport\Passport;

class DocumentMappingSyncSubscriber
{
    public function __construct(protected MappingClient $mappingClient,
                                protected Config $config,
                                protected ExceptionHandler $exceptionHandler)
    {
    }

    public function subscribe(Dispatcher $events)
    {
        if ($this->config['docprocessor.document_engine_enabled']) {
            $events->listen(ImportableColumnCreated::class, [self::class, 'createDocumentHeader']);
            $events->listen(ImportableColumnUpdated::class, [self::class, 'updateDocumentHeader']);
            $events->listen(ImportableColumnDeleted::class, [self::class, 'deleteDocumentHeader']);
        }
    }

    public function createDocumentHeader(WithImportableColumnEntity $event)
    {
        if ($event instanceof WithCauserEntity && $event->getCauser() instanceof Passport::$clientModel) {
            return;
        }

        $column = $event->getImportableColumn();

        try {
            $result = $this->mappingClient
                ->createDocumentHeader(new CreateDocumentHeaderData([
                    'headerName' => $column->header,
                    'headerAliases' => $column->aliases()->pluck('alias')->all(),
                ]));
        } catch (DocumentEngineClientException|ConnectionException $e) {
            $this->exceptionHandler->report($e);

            return;
        }

        with($column, function (ImportableColumn $column) use ($result) {
            $column->de_header_reference = $result->getHeaderReference();

            $column->saveQuietly();
        });
    }

    public function updateDocumentHeader(WithImportableColumnEntity $event)
    {
        if ($event instanceof WithCauserEntity && $event->getCauser() instanceof Passport::$clientModel) {
            return;
        }

        $column = $event->getImportableColumn();

        if (is_null($column->de_header_reference)) {
            $this->createDocumentHeader($event);

            return;
        }

        try {
            $this->mappingClient
                ->updateDocumentHeader(new UpdateDocumentHeaderData([
                    'headerReference' => $column->de_header_reference,
                    'headerName' => $column->header,
                    'headerAliases' => $column->aliases->pluck('alias')->all(),
                ]));
        } catch (DocumentEngineClientException|ConnectionException $e) {
            $this->exceptionHandler->report($e);
        }
    }

    public function deleteDocumentHeader(WithImportableColumnEntity $event)
    {
        if ($event instanceof WithCauserEntity && $event->getCauser() instanceof Passport::$clientModel) {
            return;
        }

        $column = $event->getImportableColumn();

        if (is_null($column->de_header_reference)) {
            return;
        }

        try {
            $this->mappingClient
                ->deleteDocumentHeader($column->de_header_reference);
        } catch (DocumentEngineClientException|ConnectionException $e) {
            $this->exceptionHandler->report($e);
        }
    }
}
