<?php

namespace App\Services\Pipeliner\Webhook\EventHandlers;

use App\DTO\Pipeliner\IncomingWebhookData;
use App\Integrations\Pipeliner\GraphQl\PipelinerAccountIntegration;
use App\Integrations\Pipeliner\GraphQl\PipelinerGraphQlClient;
use App\Integrations\Pipeliner\GraphQl\PipelinerOpportunityIntegration;
use App\Integrations\Pipeliner\Models\AccountFilterInput;
use App\Integrations\Pipeliner\Models\EntityFilterRelatedField;
use App\Integrations\Pipeliner\Models\EntityFilterStringField;
use App\Integrations\Pipeliner\Models\LeadOpptyContactRelationFilterInput;
use App\Integrations\Pipeliner\Models\OpportunityFilterInput;
use App\Services\Pipeliner\PipelinerTouchEntityService;
use Illuminate\Support\LazyCollection;

class ContactEventHandler implements EventHandler
{

    public function __construct(protected PipelinerOpportunityIntegration $oppIntegration,
                                protected PipelinerAccountIntegration     $accIntegration,
                                protected PipelinerTouchEntityService     $touchEntityService,
                                protected PipelinerGraphQlClient          $client)
    {
    }

    public function handle(IncomingWebhookData $data): void
    {
        if (false === $this->shouldBeHandled($data)) {
            return;
        }

        $this->touchOpportunityUsingContactId($data->entity['id']);
        $this->touchAccountUsingContactId($data->entity['id']);
    }

    private function touchOpportunityUsingContactId(string $contactId): void
    {
        $touchingOpportunities = LazyCollection::make($this->oppIntegration->simpleScroll(filter: OpportunityFilterInput::new()->contactRelations(
            LeadOpptyContactRelationFilterInput::new()->contactId(EntityFilterStringField::eq($contactId))
        )))
            ->values()
            ->pluck('id');

        if ($touchingOpportunities->isNotEmpty()) {
            $this->touchEntityService->touchOpportunityById(...$touchingOpportunities->all());
        }
    }

    private function touchAccountUsingContactId(string $contactId): void
    {
        $touchingAccounts = LazyCollection::make($this->accIntegration->simpleScroll(filter: AccountFilterInput::new()->relatedEntities(
            EntityFilterRelatedField::contact($contactId)
        )))
            ->values()
            ->pluck('id');

        if ($touchingAccounts->isNotEmpty()) {
            $this->touchEntityService->touchAccountById(...$touchingAccounts->all());
        }
    }

    private function shouldBeHandled(IncomingWebhookData $data): bool
    {
        return match ($data->event) {
            'Contact.Update' => true,
            default => false,
        };
    }
}