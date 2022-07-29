<?php

namespace App\Services\Pipeliner\Webhook\EventHandlers;

use App\DTO\Pipeliner\IncomingWebhookData;
use App\Integrations\Pipeliner\GraphQl\PipelinerGraphQlClient;
use App\Services\Pipeliner\PipelinerTouchEntityService;

class TaskEventHandler implements EventHandler
{
    public function __construct(protected PipelinerTouchEntityService $touchEntityService,
                                protected PipelinerGraphQlClient      $client)
    {
    }

    public function handle(IncomingWebhookData $data): void
    {
        if (false === $this->shouldBeHandled($data)) {
            return;
        }

        $touchingOpportunities = collect($data->entity['opportunity_relations'] ?? [])
            ->map(function (string $url): string {
                $relationResponse = $this->client->get($url)->throw();

                return $this->client->get($relationResponse->json('data.oppty'))
                    ->throw()
                    ->json('data.id');
            });

        if ($touchingOpportunities->isNotEmpty()) {
            $this->touchEntityService->touchOpportunityById(...$touchingOpportunities);
        }

        $touchingAccounts = collect($data->entity['account_relations'] ?? [])
            ->map(function (string $url): string {
                $relationResponse = $this->client->get($url)->throw();

                return $this->client->get($relationResponse->json('data.account'))
                    ->throw()
                    ->json('data.id');
            });

        if ($touchingAccounts->isNotEmpty()) {
            $this->touchEntityService->touchAccountById(...$touchingAccounts);
        }
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