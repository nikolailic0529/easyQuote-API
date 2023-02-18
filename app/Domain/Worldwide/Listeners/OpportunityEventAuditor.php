<?php

namespace App\Domain\Worldwide\Listeners;

use App\Domain\Activity\Services\ActivityLogger;
use App\Domain\Activity\Services\ChangesDetector;
use App\Domain\Worldwide\Contracts\WithOpportunityEntity;
use App\Domain\Worldwide\Events\Opportunity\OpportunityCreated;
use App\Domain\Worldwide\Events\Opportunity\OpportunityDeleted;
use App\Domain\Worldwide\Events\Opportunity\OpportunityMarkedAsLost;
use App\Domain\Worldwide\Events\Opportunity\OpportunityMarkedAsNotLost;
use App\Domain\Worldwide\Events\Opportunity\OpportunityOwnershipChanged;
use App\Domain\Worldwide\Events\Opportunity\OpportunityUpdated;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Notifications\OpportunityOwnershipChangedNotification;
use App\Domain\Worldwide\Services\Opportunity\ValidateOpportunityService;
use App\Foundation\Support\Elasticsearch\Jobs\IndexSearchableEntity;
use Illuminate\Bus\Dispatcher;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Queue\ShouldQueue;

class OpportunityEventAuditor implements ShouldQueue
{
    protected static array $logModelAttributes = [
        'primaryAccount.name',
        'primaryAccountContact.contact_representation',
        'endUser.name',
        'accountManager.fullname',
        'project_name',
        'nature_of_service',
        'renewal_month',
        'renewal_year',
        'customer_status',
        'end_user_name',
        'hardware_status',
        'region_name',
        'opportunity_start_date',
        'opportunity_end_date',
        'opportunity_closing_date',
        'customer_order_date',
        'purchase_order_date',
        'supplier_order_date',
        'supplier_order_transaction_date',
        'supplier_order_confirmation_date',
        'expected_order_date',
        'base_opportunity_amount',
        'opportunity_amount',
        'opportunity_amount_currency_code',
        'base_purchase_price',
        'purchase_price',
        'purchase_price_currency_code',
        'base_list_price',
        'list_price',
        'list_price_currency_code',
        'estimated_upsell_amount',
        'estimated_upsell_amount_currency_code',
        'margin_value',
        'campaign_name',
        'service_level_agreement_id',
        'sale_unit_name',
        'competition_name',
        'drop_in',
        'lead_source_name',
        'has_higher_sla',
        'is_multi_year',
        'has_additional_hardware',
        'has_service_credits',
        'remarks',
        'personal_rating',
        'sale_action_name',
    ];

    public function __construct(
        protected Dispatcher $dispatcher,
        protected ActivityLogger $activityLogger,
        protected ChangesDetector $changesDetector,
        protected Guard $guard,
        protected ValidateOpportunityService $validateOpportunityService
    ) {
    }

    public function subscribe(\Illuminate\Events\Dispatcher $events): array
    {
        return [
            OpportunityCreated::class => [
                [static::class, 'auditCreatedEvent'],
                [static::class, 'validateOpportunity'],
                [static::class, 'indexOpportunity'],
            ],
            OpportunityUpdated::class => [
                [static::class, 'auditUpdatedEvent'],
                [static::class, 'validateOpportunity'],
                [static::class, 'indexOpportunity'],
            ],
            OpportunityDeleted::class => [
                [static::class, 'auditDeletedEvent'],
            ],
            OpportunityOwnershipChanged::class => [
                [static::class, 'auditOwnershipChangedEvent'],
                [static::class, 'notifyAboutOwnershipChanged'],
            ],
            OpportunityMarkedAsLost::class => [
                [static::class, 'auditMarkedAsLostEvent'],
            ],
            OpportunityMarkedAsNotLost::class => [
                [static::class, 'auditMarkedAsNotLostEvent'],
            ],
        ];
    }

    public function auditMarkedAsLostEvent(OpportunityMarkedAsLost $event)
    {
        $opportunity = $event->getOpportunity();

        $this->activityLogger
            ->by($event->getCauser() ?? $this->guard->user())
            ->performedOn($opportunity)
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [
                    'status' => 'Not Lost',
                ],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'status' => 'Lost',
                    'status_reason' => $opportunity->status_reason,
                ],
            ])
            ->log('updated');
    }

    public function auditMarkedAsNotLostEvent(OpportunityMarkedAsNotLost $event)
    {
        $opportunity = $event->getOpportunity();

        $this->activityLogger
            ->by($event->getCauser() ?? $this->guard->user())
            ->performedOn($opportunity)
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [
                    'status' => 'Lost',
                ],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'status' => 'Not Lost',
                ],
            ])
            ->log('updated');
    }

    public function auditCreatedEvent(OpportunityCreated $event): void
    {
        $opportunity = $event->getOpportunity();

        $this->activityLogger
            ->performedOn($opportunity)
            ->by($event->getCauser() ?? $this->guard->user())
            ->withProperties(
                $this->changesDetector->getAttributeValuesToBeLogged(
                    $opportunity,
                    static::$logModelAttributes)
            )
            ->log('created');
    }

    public function validateOpportunity(WithOpportunityEntity $event): void
    {
        $this->validateOpportunityService->performValidation($event->getOpportunity());
    }

    public function indexOpportunity(WithOpportunityEntity $event): void
    {
        $this->dispatcher->dispatch(
            new IndexSearchableEntity($event->getOpportunity())
        );
    }

    public function auditUpdatedEvent(OpportunityUpdated $event): void
    {
        with($event, function (OpportunityUpdated $event) {
            $opportunity = $event->getOpportunity();
            $oldOpportunity = $event->getOldOpportunity();
            $this->activityLogger
                ->performedOn($opportunity)
                ->by($event->getCauser() ?? $this->guard->user())
                ->withProperties(
                    $this->changesDetector->getAttributeValuesToBeLogged(
                        model: $opportunity,
                        logAttributes: static::$logModelAttributes,
                        oldAttributeValues: $this->changesDetector->getModelChanges($oldOpportunity,
                            static::$logModelAttributes),
                        diff: true,
                    )
                )
                ->submitEmptyLogs(false)
                ->log('updated');

            $this->validateOpportunityService->performValidation($opportunity);
        });

        $this->dispatcher->dispatch(
            new IndexSearchableEntity($event->getOpportunity())
        );
    }

    public function auditDeletedEvent(OpportunityDeleted $event): void
    {
        $opportunity = $event->getOpportunity();

        $this->activityLogger
            ->performedOn($opportunity)
            ->by($event->getCauser() ?? $this->guard->user())
            ->log('deleted');
    }

    public function auditOwnershipChangedEvent(OpportunityOwnershipChanged $event): void
    {
        $getAttributes = static function (Opportunity $opportunity): array {
            return [
                'owner' => $opportunity->owner?->getIdForHumans(),
                'sales_unit' => $opportunity->salesUnit?->unit_name,
            ];
        };

        $this->activityLogger
            ->performedOn($event->opportunity)
            ->by($event->causer)
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    oldAttributeValues: $getAttributes($event->oldOpportunity),
                    newAttributeValues: $getAttributes($event->opportunity)
                )
            )
            ->submitEmptyLogs(false)
            ->log('updated');

        $this->dispatcher->dispatch(
            new IndexSearchableEntity($event->opportunity)
        );
    }

    public function notifyAboutOwnershipChanged(OpportunityOwnershipChanged $event): void
    {
        $event->opportunity->owner->notify(new OpportunityOwnershipChangedNotification($event->opportunity));
    }
}
