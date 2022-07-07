<?php

namespace App\Services\Pipeliner\Webhook\EventHandlers;

use App\DTO\Pipeliner\IncomingWebhookData;
use App\Integrations\Pipeliner\Enum\ValidationLevel;
use App\Integrations\Pipeliner\GraphQl\PipelinerOpportunityIntegration;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\LeadOpptyAccountRelationFilterInput;
use App\Integrations\Pipeliner\Models\OpportunityFilterInput;
use App\Integrations\Pipeliner\Models\UpdateOpportunityInput;
use App\Integrations\Pipeliner\Models\UpdateOpportunityInputCollection;
use App\Integrations\Pipeliner\Models\ValidationLevelCollection;

class AccountEventHandler implements EventHandler
{
    public function __construct(protected PipelinerOpportunityIntegration $oppIntegration)
    {
    }

    public function handle(IncomingWebhookData $data): void
    {
        if (false === $this->shouldBeHandled($data)) {
            return;
        }

        $this->touchOpportunityUsingAccountId($data->entity['id']);
    }

    private function touchOpportunityUsingAccountId(string $accountId): void
    {
        $opportunities = $this->oppIntegration->scroll(filter: OpportunityFilterInput::new()->accountRelations(
            LeadOpptyAccountRelationFilterInput::new()->accountId(EntityFilterStringField::eq($accountId))
        ));

        $inputCollection = [];

        foreach ($opportunities as $opp) {
            $inputCollection[] = new UpdateOpportunityInput(id: $opp->id, name: $opp->name.' ');
            $inputCollection[] = new UpdateOpportunityInput(id: $opp->id, name: $opp->name);
        }

        $this->oppIntegration->bulkUpdate(
            new UpdateOpportunityInputCollection(...$inputCollection),
            ValidationLevelCollection::from(ValidationLevel::SKIP_ALL)
        );
    }

    private function shouldBeHandled(IncomingWebhookData $data): bool
    {
        return in_array($data->event, [
            'Account.Update',
        ], true);
    }
}