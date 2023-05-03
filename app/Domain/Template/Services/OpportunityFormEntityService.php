<?php

namespace App\Domain\Template\Services;

use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\Template\DataTransferObjects\CreateOpportunityFormData;
use App\Domain\Template\DataTransferObjects\UpdateOpportunityFormData;
use App\Domain\Template\DataTransferObjects\UpdateOpportunityFormSchemaData;
use App\Domain\Template\Events\OpportunityForm\OpportunityFormCreated;
use App\Domain\Template\Events\OpportunityForm\OpportunityFormDeleted;
use App\Domain\Template\Events\OpportunityForm\OpportunityFormUpdated;
use App\Domain\Worldwide\Models\OpportunityForm;
use App\Domain\Worldwide\Models\OpportunityFormSchema;
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

    public function updateSchemaOfOpportunityForm(
        OpportunityForm $opportunityForm,
        UpdateOpportunityFormSchemaData $data
    ): OpportunityForm {
        return tap($opportunityForm, function (OpportunityForm $opportunityForm) use ($data): void {
            $opportunityFormSchema = tap($opportunityForm->formSchema ?? new OpportunityFormSchema(),
                function (OpportunityFormSchema $formSchema) use ($data) {
                    if (false === $formSchema->exists) {
                        $formSchema->setId();
                    }

                    $formSchema->form_data = $data->form_data;
                    $formSchema->sidebar_0 = $data->sidebar_0;
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
