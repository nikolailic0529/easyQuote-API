<?php

namespace App\Services\Pipeliner\Webhook\EventHandlers;

use App\DTO\Pipeliner\IncomingWebhookData;
use App\Integrations\Pipeliner\Enum\ValidationLevel;
use App\Integrations\Pipeliner\GraphQl\PipelinerGraphQlClient;
use App\Integrations\Pipeliner\GraphQl\PipelinerOpportunityIntegration;
use App\Integrations\Pipeliner\Models\UpdateOpportunityInput;
use App\Integrations\Pipeliner\Models\UpdateOpportunityInputCollection;
use App\Integrations\Pipeliner\Models\ValidationLevelCollection;

class TaskEventHandler implements EventHandler
{
    public function __construct(protected PipelinerOpportunityIntegration $oppIntegration,
                                protected PipelinerGraphQlClient          $client)
    {
    }

    public function handle(IncomingWebhookData $data): void
    {
        if (false === $this->shouldBeHandled($data)) {
            return;
        }

        foreach ($data->entity['opportunity_relations'] ?? [] as $url) {
            $relationResponse = $this->client->get($url)->throw();

            $this->touchOpportunityUsingUrl($relationResponse->json('data.oppty'));
        }
    }

    private function touchOpportunityUsingUrl(string $url): void
    {
        $opptyResponse = $this->client->get($url)->throw();

        $this->oppIntegration->bulkUpdate(
            new UpdateOpportunityInputCollection(
                new UpdateOpportunityInput(id: $opptyResponse->json('data.id'), name: $opptyResponse->json('data.name').' '),
                new UpdateOpportunityInput(id: $opptyResponse->json('data.id'), name: $opptyResponse->json('data.name'))
            ),
            ValidationLevelCollection::from(ValidationLevel::SKIP_ALL),
        );
    }

    private function shouldBeHandled(IncomingWebhookData $data): bool
    {
        return in_array($data->event, [
            'Task.Create',
            'Task.Update',
            'Task.Delete',
        ], true);
    }
}