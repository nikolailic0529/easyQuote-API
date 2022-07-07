<?php

namespace App\Services\Pipeliner;

use App\Integrations\Pipeliner\GraphQl\PipelinerAccountIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerAppointmentIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerContactIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerNoteIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerOpportunityIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerTaskIntegration;
use App\Models\Address;
use App\Models\Appointment\Appointment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Note\Note;
use App\Models\Opportunity;
use App\Models\Task\Task;
use App\Services\Pipeliner\Models\LinkedEntity;
use Illuminate\Database\Eloquent\Model;

class LinkedEntityAggregateService
{
    public function __construct(protected PipelinerAccountIntegration     $accountIntegration,
                                protected PipelinerOpportunityIntegration $opportunityIntegration,
                                protected PipelinerContactIntegration     $contactIntegration,
                                protected PipelinerAppointmentIntegration $appointmentIntegration,
                                protected PipelinerTaskIntegration        $taskIntegration,
                                protected PipelinerNoteIntegration        $noteIntegration)
    {
    }

    /**
     * @return LinkedEntity[]
     */
    public function aggregate(): array
    {
        $models = [
            Company::class,
            Opportunity::class,
            Address::class,
            Contact::class,
            Appointment::class,
            Task::class,
            Note::class,
        ];

        return collect($models)
            ->reduce(function (array $items, string $class) {
                return [...$items, ...$this->aggregateEntitiesOfModel($class)];
            }, []);
    }

    private function aggregateEntitiesOfModel(string $class): array
    {
        /** @var Model $model */
        $model = (new $class);

        $entityName = class_basename($model);

        $entitiesOfModel = $model->newQuery()
            ->whereNotNull('pl_reference')
            ->toBase()
            ->get(["{$model->getQualifiedKeyName()} as id", $model->qualifyColumn('pl_reference')]);

        $refs = $entitiesOfModel->pluck('pl_reference')->all();

        $validRefMap = $this->validateRefs($class, $refs);

        return $entitiesOfModel->map(static function (object $item) use ($entityName, $validRefMap): LinkedEntity {
            return LinkedEntity::fromArray([
                'id' => $item->id,
                'pl_reference' => $item->pl_reference,
                'entity_name' => $entityName,
                'is_valid' => $validRefMap[$item->pl_reference],
            ]);
        })
            ->all();
    }

    private function validateRefs(string $class, array $refs): array
    {
        $result = match ($class) {
            Company::class => $this->accountIntegration->getByIds(...$refs),
            Opportunity::class => $this->opportunityIntegration->getByIds(...$refs),
            Address::class, Contact::class => $this->contactIntegration->getByIds(...$refs),
            Appointment::class => $this->appointmentIntegration->getByIds(...$refs),
            Task::class => $this->taskIntegration->getByIds(...$refs),
            Note::class => $this->noteIntegration->getByIds(...$refs),
            default => [],
        };

        $resultMap = collect($result)->keyBy('id')->all();

        return collect($refs)->mapWithKeys(static function (string $ref) use ($resultMap): array {
            return [$ref => isset($resultMap[$ref])];
        })
            ->all();
    }
}