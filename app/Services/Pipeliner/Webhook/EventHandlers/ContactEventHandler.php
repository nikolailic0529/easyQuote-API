<?php

namespace App\Services\Pipeliner\Webhook\EventHandlers;

use App\DTO\Pipeliner\IncomingWebhookData;
use App\Integrations\Pipeliner\Enum\ValidationLevel;
use App\Integrations\Pipeliner\GraphQl\PipelinerGraphQlClient;
use App\Integrations\Pipeliner\GraphQl\PipelinerOpportunityIntegration;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\LeadOpptyContactRelationFilterInput;
use App\Integrations\Pipeliner\Models\OpportunityFilterInput;
use App\Integrations\Pipeliner\Models\UpdateOpportunityInput;
use App\Integrations\Pipeliner\Models\UpdateOpportunityInputCollection;
use App\Integrations\Pipeliner\Models\ValidationLevelCollection;

class ContactEventHandler implements EventHandler
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

        $this->touchOpportunityUsingContactId($data->entity['id']);
    }

    private function touchOpportunityUsingContactId(string $contactId)
    {
        $opportunities = $this->oppIntegration->scroll(filter: OpportunityFilterInput::new()->contactRelations(
            LeadOpptyContactRelationFilterInput::new()->contactId(EntityFilterStringField::eq($contactId))
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
        return match ($data->event) {
            'Contact.Update' => true,
            default => false,
        };
    }
}