<?php

namespace App\Domain\Pipeliner\Services;

use App\Domain\Address\Models\Address;
use App\Domain\Appointment\Models\Appointment;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Note\Models\Note;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerAccountIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerAppointmentIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerContactIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerNoteIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerOpportunityIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerTaskIntegration;
use App\Domain\Pipeliner\Services\Models\LinkedEntity;
use App\Domain\Task\Models\Task;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Database\Eloquent\Model;

class LinkedEntityAggregateService
{
    const VALIDATE_REFS = 1 << 0;

    protected int $flags = 0;

    public function __construct(protected PipelinerAccountIntegration $accountIntegration,
                                protected PipelinerOpportunityIntegration $opportunityIntegration,
                                protected PipelinerContactIntegration $contactIntegration,
                                protected PipelinerAppointmentIntegration $appointmentIntegration,
                                protected PipelinerTaskIntegration $taskIntegration,
                                protected PipelinerNoteIntegration $noteIntegration)
    {
    }

    /**
     * @return LinkedEntity[]
     */
    public function aggregate(int $flags = 0): array
    {
        $this->flags = $flags;

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
        $model = (new $class());

        $entityName = class_basename($model);

        $entitiesOfModel = $model->newQuery()
            ->whereNotNull('pl_reference')
            ->toBase()
            ->get(["{$model->getQualifiedKeyName()} as id", $model->qualifyColumn('pl_reference')]);

        $refs = $entitiesOfModel->pluck('pl_reference')->all();

        $validRefMap = ($this->flags & self::VALIDATE_REFS) === self::VALIDATE_REFS
            ? $this->validateRefs($class, $refs)
            : [];

        return $entitiesOfModel->map(static function (object $item) use ($entityName, $validRefMap): LinkedEntity {
            return LinkedEntity::fromArray([
                'id' => $item->id,
                'pl_reference' => $item->pl_reference,
                'entity_name' => $entityName,
                'is_valid' => $validRefMap[$item->pl_reference] ?? null,
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
