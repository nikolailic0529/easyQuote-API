<?php

namespace App\Listeners;

use App\Events\Contact\ContactCreated;
use App\Events\Contact\ContactDeleted;
use App\Events\Contact\ContactUpdated;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use App\Services\Opportunity\ValidateOpportunityService;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

class ContactEventAuditor implements ShouldQueue
{
    protected static array $logAttributes = [
        'contact_type',
        'job_title',
        'first_name',
        'last_name',
        'mobile',
        'phone',
        'email',
        'is_verified',
    ];

    public function __construct(
        protected readonly ActivityLogger $activityLogger,
        protected readonly ChangesDetector $changesDetector,
        protected readonly ValidateOpportunityService $validateOpportunityService,
    ) {
    }


    public function subscribe(Dispatcher $events): array
    {
        return [
            ContactCreated::class => [
                [self::class, 'auditCreatedEvent'],
                [static::class, 'touchRelatedCompaniesOnContactCreated'],
                [static::class, 'revalidateRelatedOpportunitiesOnContactCreated'],
            ],
            ContactUpdated::class => [
                [self::class, 'auditUpdatedEvent'],
                [static::class, 'touchRelatedCompaniesOnContactUpdated'],
                [static::class, 'revalidateRelatedOpportunitiesOnContactUpdated'],
            ],
            ContactDeleted::class => [
                [self::class, 'auditDeletedEvent'],
                [static::class, 'touchRelatedCompaniesOnContactDeleted'],
                [static::class, 'revalidateRelatedOpportunitiesOnContactDeleted'],
            ],
        ];
    }

    public function revalidateRelatedOpportunitiesOnContactCreated(ContactCreated $event): void
    {
        $this->revalidateRelatedOpportunitiesOf($event->contact);
    }

    private function revalidateRelatedOpportunitiesOf(Contact $contact): void
    {
        $contact->companies()->lazyById(10)
            ->each(function (Company $company): void {
                $opportunities = Opportunity::query()
                    ->where(static function (Builder $builder) use ($company): void {
                        $builder->whereBelongsTo($company, 'primaryAccount')
                            ->orWhereBelongsTo($company, 'endUser');
                    })
                    ->lazyById(100);

                foreach ($opportunities as $opp) {
                    $this->validateOpportunityService->performValidation($opp);
                }
            });
    }

    public function revalidateRelatedOpportunitiesOnContactUpdated(ContactUpdated $event): void
    {
        $this->revalidateRelatedOpportunitiesOf($event->newContact);
    }

    public function revalidateRelatedOpportunitiesOnContactDeleted(ContactDeleted $event): void
    {
        $this->revalidateRelatedOpportunitiesOf($event->contact);
    }

    public function touchRelatedCompaniesOnContactCreated(ContactCreated $event): void
    {
        $event->contact->companies()->touch();
    }

    public function touchRelatedCompaniesOnContactUpdated(ContactUpdated $event): void
    {
        $event->newContact->companies()->touch();
    }

    public function touchRelatedCompaniesOnContactDeleted(ContactDeleted $event): void
    {
        $event->contact->companies()->touch();
    }

    public function auditCreatedEvent(ContactCreated $event): void
    {
        $this->activityLogger
            ->on($event->contact)
            ->by($event->causer)
            ->withProperties(
                $this->changesDetector->getAttributeValuesToBeLogged($event->contact, self::$logAttributes)
            )
            ->log('created');
    }

    public function auditUpdatedEvent(ContactUpdated $event): void
    {
        $this->activityLogger
            ->on($event->contact)
            ->by($event->causer)
            ->withProperties(
                $this->changesDetector->getAttributeValuesToBeLogged(
                    model: $event->newContact, logAttributes: self::$logAttributes,
                    oldAttributeValues: $this->changesDetector->getModelChanges($event->contact,
                        self::$logAttributes),
                    diff: true
                )
            )
            ->submitEmptyLogs(false)
            ->log('updated');
    }

    public function auditDeletedEvent(ContactDeleted $event): void
    {
        $this->activityLogger
            ->on($event->contact)
            ->by($event->causer)
            ->log('deleted');
    }
}
