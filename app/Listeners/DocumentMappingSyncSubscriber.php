<?php

namespace App\Listeners;

use App\Contracts\WithCauserEntity;
use App\Contracts\WithImportableColumnEntity;
use App\Events\ImportableColumn\ImportableColumnCreated;
use App\Events\ImportableColumn\ImportableColumnDeleted;
use App\Events\ImportableColumn\ImportableColumnUpdated;
use App\Models\QuoteFile\ImportableColumn;
use App\Services\DocumentEngine\Exceptions\DocumentEngineClientException;
use App\Services\DocumentEngine\MappingClient;
use App\Services\DocumentEngine\Models\CreateDocumentHeaderData;
use App\Services\DocumentEngine\Models\UpdateDocumentHeaderData;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\ConnectionException;
use Laravel\Passport\Passport;

class DocumentMappingSyncSubscriber
{
    public function __construct(protected MappingClient    $mappingClient,
                                protected Config           $config,
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
        } catch (DocumentEngineClientException | ConnectionException $e) {
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
        } catch (DocumentEngineClientException | ConnectionException $e) {
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
        } catch (DocumentEngineClientException | ConnectionException $e) {
            $this->exceptionHandler->report($e);
        }
    }
}