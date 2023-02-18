<?php

namespace App\Domain\Pipeliner\Services\Webhook\EventHandlers;

use App\Domain\Pipeliner\DataTransferObjects\IncomingWebhookData;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerOpportunityIntegration;
use App\Domain\Pipeliner\Integration\Models\EntityFilterStringField;
use App\Domain\Pipeliner\Integration\Models\LeadOpptyAccountRelationFilterInput;
use App\Domain\Pipeliner\Integration\Models\OpportunityFilterInput;
use App\Domain\Pipeliner\Services\PipelinerTouchEntityService;
use Illuminate\Support\LazyCollection;

class AccountRelationEventHandler implements EventHandler
{
    public function __construct(protected PipelinerOpportunityIntegration $oppIntegration,
                                protected PipelinerTouchEntityService $touchEntityService)
    {
    }

    public function handle(IncomingWebhookData $data): void
    {
        if (false === $this->shouldBeHandled($data)) {
            return;
        }

        $touchingOpportunity = LazyCollection::make(function () use ($data): \Generator {
            yield from $this->oppIntegration->simpleScroll(filter: OpportunityFilterInput::new()->accountRelations(
                LeadOpptyAccountRelationFilterInput::new()->accountId(EntityFilterStringField::eq($data->entity['account_id']))
            ));
        })
            ->values()
            ->pluck('id');

        if ($touchingOpportunity->isNotEmpty()) {
            $this->touchEntityService->touchOpportunityById(...$touchingOpportunity->all());
        }

        $this->touchEntityService->touchAccountById($data->entity['account_id']);
    }

    private function shouldBeHandled(IncomingWebhookData $data): bool
    {
        return in_array($data->event, [
            'ContactAccountRelation.Create',
            'ContactAccountRelation.Update',
            'ContactAccountRelation.Delete',
        ], true);
    }
}
