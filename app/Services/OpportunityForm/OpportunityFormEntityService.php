<?php

namespace App\Services\OpportunityForm;

use App\Contracts\CauserAware;
use App\DTO\OpportunityForm\CreateOpportunityFormData;
use App\DTO\OpportunityForm\UpdateOpportunityFormData;
use App\Events\OpportunityForm\OpportunityFormCreated;
use App\Events\OpportunityForm\OpportunityFormDeleted;
use App\Events\OpportunityForm\OpportunityFormUpdated;
use App\Models\OpportunityForm\OpportunityForm;
use App\Models\OpportunityForm\OpportunityFormSchema;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Webpatser\Uuid\Uuid;

class OpportunityFormEntityService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected ConnectionInterface $connection,
        protected EventDispatcher $eventDispatcher
    ) {
    }

    public function createOpportunityForm(CreateOpportunityFormData $data): OpportunityForm
    {
        return tap(new OpportunityForm(), function (OpportunityForm $opportunityForm) use ($data) {
            $opportunityForm->user()->associate($this->causer);
            $opportunityForm->pipeline()->associate($data->pipeline_id);

            $formSchema = tap(new OpportunityFormSchema(), function (OpportunityFormSchema $formSchema) {
                $formSchema->{$formSchema->getKeyName()} = (string) Uuid::generate(4);
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

    public function replicateOpportunityForm(
        CreateOpportunityFormData $data,
        OpportunityForm $form
    ): OpportunityForm {
        return tap($form->replicate(['is_not_deleted']), function (OpportunityForm $form) use ($data): void {
            $form->user()->associate($this->causer);
            $form->forceFill($data->all());
            $form->is_system = false;

            $form->formSchema()->associate($form->formSchema->replicate()->setId());

            $this->connection->transaction(static function () use ($form): void {
                $form->formSchema->save();
                $form->save();
            });
        });
    }

    public function updateOpportunityForm(
        OpportunityForm $opportunityForm,
        UpdateOpportunityFormData $data
    ): OpportunityForm {
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

            $opportunityFormSchema = tap($opportunityForm->formSchema ?? new OpportunityFormSchema(),
                function (OpportunityFormSchema $formSchema) use ($schema) {
                    if (false === $formSchema->exists) {
                        $formSchema->{$formSchema->getKeyName()} = (string) Uuid::generate(4);
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

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn () => $this->causer = $causer);
    }
}
