<?php

namespace App\Domain\Pipeliner\Notifications;

use App\Domain\Company\Models\Company;
use App\Domain\Eloquent\Contracts\ProvidesIdForHumans;
use App\Domain\Priority\Enum\Priority;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ModelSyncFailedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected readonly Model $model
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
            'priority' => Priority::Low,
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

    protected function getMessage(): string
    {
        $modelName = Str::headline(class_basename($this->model));
        $modelIdForHumans = $this->modelIdForHumans($this->model);

        return sprintf('Data sync of %s [%s] has been failed.', $modelName, $modelIdForHumans);
    }

    private function modelIdForHumans(Model $model): string
    {
        if ($model instanceof ProvidesIdForHumans) {
            return $model->getIdForHumans();
        }

        return $model->getKey();
    }
}
