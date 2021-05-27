<?php

namespace App\Services\OpportunityForm;

use App\DTO\OpportunityForm\CreateOpportunityFormData;
use App\DTO\OpportunityForm\UpdateOpportunityFormData;
use App\Events\OpportunityForm\OpportunityFormCreated;
use App\Events\OpportunityForm\OpportunityFormDeleted;
use App\Events\OpportunityForm\OpportunityFormUpdated;
use App\Models\OpportunityForm\OpportunityForm;
use App\Models\OpportunityForm\OpportunityFormSchema;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Webpatser\Uuid\Uuid;

class OpportunityFormEntityService
{
    protected ConnectionInterface $connection;
    protected EventDispatcher $eventDispatcher;

    public function __construct(ConnectionInterface $connection, EventDispatcher $eventDispatcher)
    {
        $this->connection = $connection;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createOpportunityForm(CreateOpportunityFormData $data): OpportunityForm
    {
        return tap(new OpportunityForm(), function (OpportunityForm $opportunityForm) use ($data) {
            $opportunityForm->user()->associate($data->user_id);
            $opportunityForm->pipeline()->associate($data->pipeline_id);

            $formSchema = tap(new OpportunityFormSchema(), function (OpportunityFormSchema $formSchema) {
                $formSchema->{$formSchema->getKeyName()} = (string)Uuid::generate(4);
                $formSchema->form_data = [];
            });

            $opportunityForm->formSchema()->associate($formSchema);

            $this->connection->transaction(function () use ($opportunityForm) {
                $opportunityForm->formSchema->save();
                $opportunityForm->save();
            });

            $this->eventDispatcher->dispatch(
                new OpportunityFormCreated($opportunityForm)
            );
        });
    }

    public function updateOpportunityForm(OpportunityForm $opportunityForm, UpdateOpportunityFormData $data): OpportunityForm
    {
        return tap($opportunityForm, function (OpportunityForm $opportunityForm) use ($data) {
            $opportunityForm->pipeline()->associate($data->pipeline_id);

            $this->connection->transaction(function () use ($opportunityForm) {
                $opportunityForm->save();
            });

            $this->eventDispatcher->dispatch(
                new OpportunityFormUpdated($opportunityForm)
            );
        });
    }

    public function updateSchemaOfOpportunityForm(OpportunityForm $opportunityForm, array $schema): OpportunityForm
    {
        return tap($opportunityForm, function (OpportunityForm $opportunityForm) use ($schema) {

            $opportunityFormSchema = tap($opportunityForm->formSchema ?? new OpportunityFormSchema(), function (OpportunityFormSchema $formSchema) use ($schema) {
                if (false === $formSchema->exists) {
                    $formSchema->{$formSchema->getKeyName()} = (string)Uuid::generate(4);
                }

                $formSchema->form_data = $schema;
            });

            $opportunityForm->formSchema()->associate($opportunityFormSchema);

            $this->connection->transaction(function () use ($opportunityForm) {

                $opportunityForm->formSchema->save();
                $opportunityForm->save();

            });
        });
    }

    public function deleteOpportunityForm(OpportunityForm $opportunityForm): void
    {
        $this->connection->transaction(function () use ($opportunityForm) {
            $opportunityForm->delete();
        });

        $this->eventDispatcher->dispatch(
            new OpportunityFormDeleted($opportunityForm)
        );
    }
}
