<?php

namespace App\Domain\Pipeliner\Services\Webhook;

use App\Domain\Pipeliner\Integration\Enum\EventEnum;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerWebhookEntityIntegration;
use App\Domain\Pipeliner\Integration\Models\CreateWebhookInput;
use App\Domain\Pipeliner\Integration\Models\WebhookEntity;
use App\Domain\Pipeliner\Models\PipelinerWebhook;
use App\Domain\Pipeliner\Services\Exceptions\WebhookRegistrarException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class WebhookRegistrarService
{
    const LOCAL_ONLY = 1 << 0;

    public function __construct(
        protected PipelinerWebhookEntityIntegration $integration,
        protected WebhookEntityService $entityService
    ) {
    }

    public function getWebhooks(int $mode = self::LOCAL_ONLY): Collection
    {
        if (($mode & self::LOCAL_ONLY) === self::LOCAL_ONLY) {
            $ids = PipelinerWebhook::query()->pluck('pl_reference')->unique()->values();

            if ($ids->isEmpty()) {
                return Collection::empty();
            }

            return Collection::make($this->integration->getByIds(
                ...$ids->all()
            ));
        }

        return Collection::make($this->integration->getAll());
    }

    public function registerWebhook(string $url = null): WebhookEntity
    {
        $url ??= route('pipeliner.webhook');

        $input = new CreateWebhookInput(
            url: $url,
            events: [
                EventEnum::AccountUpdate,
                EventEnum::ContactAccountRelationAll,
                EventEnum::ContactUpdate,
                EventEnum::TaskCreate,
                EventEnum::TaskUpdate,
                EventEnum::TaskDelete,
                EventEnum::AppointmentCreate,
                EventEnum::AppointmentUpdate,
                EventEnum::AppointmentDelete,
                EventEnum::OpportunityDocumentLinked,
                EventEnum::AccountDocumentLinked,
            ],
            insecureSsl: true,
            signature: Str::uuid(),
            options: json_encode(config('pipeliner.webhook.options') ?? [])
        );

        $webhook = $this->integration->create($input);

        $this->entityService->createWebhookFromPipelinerEntity($webhook);

        return $webhook;
    }

    public function deleteWebhook(string $reference): void
    {
        /* @var PipelinerWebhook $model */
        try {
            $model = PipelinerWebhook::query()->where('pl_reference', $reference)->sole();
        } catch (ModelNotFoundException $e) {
            throw WebhookRegistrarException::webhookNotFound($reference);
        }

        $this->integration->delete($model->pl_reference);
        $this->entityService->deleteWebhook($model);
    }
}
