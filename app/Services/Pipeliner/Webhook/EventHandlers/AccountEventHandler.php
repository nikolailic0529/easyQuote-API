<?php

namespace App\Services\Pipeliner\Webhook\EventHandlers;

use App\DTO\Pipeliner\IncomingWebhookData;
use App\Integrations\Pipeliner\GraphQl\PipelinerOpportunityIntegration;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\LeadOpptyAccountRelationFilterInput;
use App\Integrations\Pipeliner\Models\OpportunityFilterInput;
use App\Services\Pipeliner\PipelinerTouchEntityService;
use Illuminate\Support\LazyCollection;

class AccountEventHandler implements EventHandler
{
    public function __construct(protected PipelinerOpportunityIntegration $oppIntegration,
                                protected PipelinerTouchEntityService     $touchEntityService)
    {
    }

    public function handle(IncomingWebhookData $data): void
    {
        if (false === $this->shouldBeHandled($data)) {
            return;
        }

        $touchingOpportunity = LazyCollection::make($this->oppIntegration->simpleScroll(filter: OpportunityFilterInput::new()->accountRelations(
            LeadOpptyAccountRelationFilterInput::new()->accountId(EntityFilterStringField::eq($data->entity['id']))
        )))
            ->values()
            ->pluck('id');

        if ($touchingOpportunity->isNotEmpty()) {
            $this->touchEntityService->touchOpportunityById(...$touchingOpportunity->all());
        }

        $this->touchEntityService->touchAccountById($data->entity['id']);
    }

    private function shouldBeHandled(IncomingWebhookData $data): bool
    {
        return in_array($data->event, [
            'Account.Update',
            'Account.DocumentLinked',
        ], true);
    }
}