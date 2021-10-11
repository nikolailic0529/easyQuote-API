<?php

namespace App\Listeners;

use App\Events\{Opportunity\OpportunityBatchFilesImported,
    Opportunity\OpportunityCreated,
    Opportunity\OpportunityDeleted,
    Opportunity\OpportunityMarkedAsLost,
    Opportunity\OpportunityMarkedAsNotLost,
    Opportunity\OpportunityUpdated};
use App\Jobs\IndexSearchableEntity;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use Illuminate\Bus\Dispatcher;

class OpportunityEventAuditor
{
    protected static array $logModelAttributes = [
        'primaryAccount.name',
        'primaryAccountContact.contact_representation',
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

    public function __construct(protected Dispatcher      $dispatcher,
                                protected ActivityLogger  $activityLogger,
                                protected ChangesDetector $changesDetector)
    {
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @param \Illuminate\Events\Dispatcher $events
     */
    public function subscribe(\Illuminate\Events\Dispatcher $events)
    {
        $events->listen(OpportunityCreated::class, [$this, 'handleCreatedEvent']);
        $events->listen(OpportunityUpdated::class, [$this, 'handleUpdatedEvent']);
        $events->listen(OpportunityDeleted::class, [$this, 'handleDeletedEvent']);
        $events->listen(OpportunityMarkedAsLost::class, [$this, 'handleMarkedAsLostEvent']);
        $events->listen(OpportunityMarkedAsNotLost::class, [$this, 'handleMarkedAsNotLostEvent']);
        $events->listen(OpportunityBatchFilesImported::class, [$this, 'handleBatchFilesImportedEvent']);
    }

    public function handleBatchFilesImportedEvent(OpportunityBatchFilesImported $event)
    {
        // TODO: handle the event.
    }

    public function handleMarkedAsLostEvent(OpportunityMarkedAsLost $event)
    {
        $opportunity = $event->getOpportunity();

        $this->activityLogger
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

    public function handleMarkedAsNotLostEvent(OpportunityMarkedAsNotLost $event)
    {
        $opportunity = $event->getOpportunity();

        $this->activityLogger
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

    public function handleCreatedEvent(OpportunityCreated $event)
    {
        $opportunity = $event->getOpportunity();

        $this->activityLogger
            ->performedOn($opportunity)
            ->by($event->getCauser())
            ->withProperties(
                $this->changesDetector->getAttributeValuesToBeLogged(
                    $opportunity, static::$logModelAttributes)
            )
            ->log('created');

        $this->dispatcher->dispatch(
            new IndexSearchableEntity($opportunity)
        );
    }

    public function handleUpdatedEvent(OpportunityUpdated $event)
    {
        with($event, function (OpportunityUpdated $event) {
            $opportunity = $event->getOpportunity();
            $oldOpportunity = $event->getOldOpportunity();
            $this->activityLogger
                ->performedOn($opportunity)
                ->by($event->getCauser())
                ->withProperties(
                    $this->changesDetector->getAttributeValuesToBeLogged(
                        $opportunity, static::$logModelAttributes,
                        $this->changesDetector->getModelChanges($oldOpportunity, static::$logModelAttributes)
                    )
                )
                ->log('updated');
        });

        $this->dispatcher->dispatch(
            new IndexSearchableEntity($event->getOpportunity())
        );
    }

    public function handleDeletedEvent(OpportunityDeleted $event)
    {
        $opportunity = $event->getOpportunity();

        $this->activityLogger
            ->performedOn($opportunity)
            ->by($event->getCauser())
            ->log('deleted');
    }
}
