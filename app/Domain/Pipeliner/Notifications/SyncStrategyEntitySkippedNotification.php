<?php

namespace App\Domain\Pipeliner\Notifications;

use App\Domain\Company\Models\Company;
use App\Domain\Pipeliner\Integration\Models\AccountEntity;
use App\Domain\Pipeliner\Integration\Models\OpportunityEntity;
use App\Domain\Priority\Enum\Priority;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

class SyncStrategyEntitySkippedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected readonly object $entity,
        protected readonly ?Model $model,
        protected readonly array $errors,
    ) {
    }

    public function via(mixed $notifiable): array
    {
        return ['database.custom'];
    }

    public function toDatabase(mixed $notifiable): array
    {
        return [
            'message' => $this->getMessage(),
            'priority' => Priority::High,
            'url' => $this->getUrl(),
        ];
    }

    private function getUrl(): ?string
    {
        if (!$this->model) {
            return null;
        }

        return match ($this->model::class) {
            Opportunity::class => ui_route('opportunities.update', ['opportunity' => $this->model]),
            Company::class => ui_route('companies.update', ['company' => $this->model]),
            default => null,
        };
    }

    protected function getMessage(): string
    {
        $entityName = Str::of($this->entity::class)->classBasename()->beforeLast('Entity')->headline();

        $errors = collect($this->errors);

        return Blade::render(<<<MSG
Unable pull {{ \$entity_name }} [{{ \$entity_id }}] from pipeliner due to errors.
@isset(\$errors)
{!! \$errors !!}@endisset
MSG,
            [
                'entity_id' => $this->entityIdForHumans($this->entity),
                'entity_name' => $entityName,
                'errors' => $errors->isNotEmpty() ? $errors->join("\n") : null,
            ]);
    }

    private function entityIdForHumans(object $entity): string
    {
        if ($entity instanceof OpportunityEntity) {
            return $entity->name;
        }

        if ($entity instanceof AccountEntity) {
            return $entity->name;
        }

        return $entity->id;
    }
}
