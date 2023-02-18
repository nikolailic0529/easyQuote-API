<?php

namespace App\Domain\Pipeliner\Services\Webhook\EventHandlers;

use App\Domain\Pipeliner\DataTransferObjects\IncomingWebhookData;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerAccountIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerGraphQlClient;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerOpportunityIntegration;
use App\Domain\Pipeliner\Integration\Models\AccountFilterInput;
use App\Domain\Pipeliner\Integration\Models\EntityFilterRelatedField;
use App\Domain\Pipeliner\Integration\Models\EntityFilterStringField;
use App\Domain\Pipeliner\Integration\Models\LeadOpptyContactRelationFilterInput;
use App\Domain\Pipeliner\Integration\Models\OpportunityFilterInput;
use App\Domain\Pipeliner\Services\PipelinerTouchEntityService;
use Illuminate\Support\LazyCollection;

class ContactEventHandler implements EventHandler
{
    public function __construct(protected PipelinerOpportunityIntegration $oppIntegration,
                                protected PipelinerAccountIntegration $accIntegration,
                                protected PipelinerTouchEntityService $touchEntityService,
                                protected PipelinerGraphQlClient $client)
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
        $touchingOpportunities = LazyCollection::make(function () use ($contactId): \Generator {
            yield from $this->oppIntegration->simpleScroll(filter: OpportunityFilterInput::new()->contactRelations(
                LeadOpptyContactRelationFilterInput::new()->contactId(EntityFilterStringField::eq($contactId))
            ));
        })
            ->values()
            ->pluck('id');

        if ($touchingOpportunities->isNotEmpty()) {
            $this->touchEntityService->touchOpportunityById(...$touchingOpportunities->all());
        }
    }

    private function touchAccountUsingContactId(string $contactId): void
    {
        $touchingAccounts = LazyCollection::make(function () use ($contactId): \Generator {
            yield from $this->accIntegration->simpleScroll(filter: AccountFilterInput::new()->relatedEntities(
                EntityFilterRelatedField::contact($contactId)
            ));
        })
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
