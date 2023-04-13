<?php

namespace App\Domain\Pipeliner\Notifications;

use App\Domain\Company\Models\Company;
use App\Domain\Priority\Enum\Priority;
use App\Domain\Shared\Eloquent\Contracts\ProvidesIdForHumans;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Blade;

class SyncStrategyModelSkippedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected readonly Model $model,
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
            'subject_id' => $this->model->getKey(),
            'subject_type' => $this->model->getMorphClass(),
        ];
    }

    private function getUrl(): ?string
    {
        return match ($this->model::class) {
            Opportunity::class => ui_route('opportunities.update', ['opportunity' => $this->model]),
            Company::class => ui_route('companies.update', ['company' => $this->model]),
            default => null,
        };
    }

    private function modelIdForHumans(Model $model): string
    {
        if ($model instanceof ProvidesIdForHumans) {
            return $model->getIdForHumans();
        }

        return $model->getKey();
    }

    protected function getMessage(): string
    {
        $modelName = class_basename($this->model);

        $errors = collect($this->errors);

        return Blade::render(<<<MSG
Unable push {{ \$model_name }} [{{ \$model_id }}] to pipeliner due to errors.
@isset(\$errors)
{!! \$errors !!}@endisset
MSG,
            [
                'model_id' => $this->modelIdForHumans($this->model),
                'model_name' => $modelName,
                'errors' => $errors->isNotEmpty() ? $errors->join("\n") : null,
            ]);
    }
}
