<?php

namespace App\Domain\Company\Listeners;

use App\Domain\Activity\Services\ActivityLogger;
use App\Domain\Activity\Services\ChangesDetector;
use App\Domain\Address\Models\Address;
use App\Domain\Company\Events\CompanyCreated;
use App\Domain\Company\Events\CompanyDeleted;
use App\Domain\Company\Events\CompanyOwnershipChanged;
use App\Domain\Company\Events\CompanyUpdated;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Notifications\CompanyOwnershipChangedNotification;
use App\Domain\Contact\Models\Contact;
use App\Domain\Worldwide\Jobs\ValidateOpportunitiesOfCompany;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Services\Opportunity\ValidateOpportunityService;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;

class CompanyEventAuditor implements ShouldQueue
{
    protected static array $logAttributes = [
        'name',
        'vat',
        'type',
        'email',
        'website',
        'phone',
        'registered_number',
        'employees_number',
        'creation_date',
        'defaultVendor.name',
        'defaultCountry.name',
        'defaultTemplate.name',
    ];

    public function __construct(
        protected readonly ActivityLogger $activityLogger,
        protected readonly ChangesDetector $changesDetector,
        protected readonly BusDispatcher $busDispatcher,
        protected readonly ValidateOpportunityService $validateOpportunityService,
    ) {
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            CompanyCreated::class => [
                [static::class, 'auditCreatedEvent'],
            ],
            CompanyUpdated::class => [
                [self::class, 'touchRelatedOpportunitiesOnCompanyUpdated'],
                [self::class, 'revalidateRelatedOpportunities'],
                [self::class, 'auditUpdatedEvent'],
            ],
            CompanyDeleted::class => [
                [self::class, 'auditDeletedEvent'],
            ],
            CompanyOwnershipChanged::class => [
                [self::class, 'touchRelatedOpportunitiesOnCompanyOwnershipChanged'],
                [self::class, 'auditOwnershipChangedEvent'],
                [self::class, 'notifyAboutOwnershipChanged'],
            ],
        ];
    }

    public function auditCreatedEvent(CompanyCreated $event): void
    {
        $company = $event->company;

        $this->activityLogger
            ->on($company)
            ->by($event->causer)
            ->withProperties([
                ChangesDetector::NEW_ATTRS_KEY => $this->getAttributesToBeLogged($company),
            ])
            ->log('created');
    }

    public function auditUpdatedEvent(CompanyUpdated $event): void
    {
        $this->activityLogger
            ->on($event->company)
            ->by($event->causer)
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    oldAttributeValues: $this->getAttributesToBeLogged($event->oldCompany),
                    newAttributeValues: $this->getAttributesToBeLogged($event->company),
                )
            )
            ->submitEmptyLogs(false)
            ->log('updated');

        $this->busDispatcher->dispatch(new ValidateOpportunitiesOfCompany($event->company));
    }

    public function revalidateRelatedOpportunities(CompanyUpdated $event): void
    {
        $opportunities = Opportunity::query()
            ->where(static function (Builder $builder) use ($event): void {
                $builder->whereBelongsTo($event->company, 'primaryAccount')
                    ->orWhereBelongsTo($event->company, 'endUser');
            })
            ->lazyById(100);

        foreach ($opportunities as $opp) {
            $this->validateOpportunityService->performValidation($opp);
        }
    }

    public function auditDeletedEvent(CompanyDeleted $event): void
    {
        $this->activityLogger
            ->on($event->company)
            ->by($event->causer)
            ->log('deleted');
    }

    public function auditOwnershipChangedEvent(CompanyOwnershipChanged $event): void
    {
        $getAttributes = static function (Company $company): array {
            return [
                'owner' => $company->owner?->getIdForHumans(),
                'sales_unit' => $company->salesUnit?->unit_name,
            ];
        };

        $this->activityLogger
            ->on($event->company)
            ->by($event->causer)
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    oldAttributeValues: $getAttributes($event->oldCompany),
                    newAttributeValues: $getAttributes($event->company),
                )
            )
            ->submitEmptyLogs(false)
            ->log('updated');
    }

    public function notifyAboutOwnershipChanged(CompanyOwnershipChanged $event): void
    {
        $event->company->owner->notify(new CompanyOwnershipChangedNotification($event->company));
    }

    public function touchRelatedOpportunitiesOnCompanyOwnershipChanged(CompanyOwnershipChanged $event)
    {
        $this->touchRelatedOpportunitiesOf($event->company);
    }

    public function touchRelatedOpportunitiesOnCompanyUpdated(CompanyUpdated $event)
    {
        if (($event->flags & CompanyUpdated::ATTRIBUTES_CHANGED) || ($event->flags & CompanyUpdated::RELATIONS_CHANGED)) {
            $this->touchRelatedOpportunitiesOf($event->company);
        }
    }

    protected function touchRelatedOpportunitiesOf(Company $company): void
    {
        $company->opportunities()->touch();
        $company->opportunitiesWhereEndUser()->touch();
    }

    protected function getAttributesToBeLogged(Company $company): array
    {
        $newAttributes = $this->changesDetector->getModelChanges($company, self::$logAttributes);

        return array_merge($newAttributes, [
            'status' => $company->status->name,
            'addresses' => $company->addresses->map(fn (Address $address) => "\{$address->address_representation\}")
                ->implode(', '),
            'contacts' => $company->contacts->map(fn (Contact $contact) => "\{$contact->contact_representation\}")
                ->implode(', '),
            'vendors' => $company->vendors->pluck('short_code')->implode(', '),
            'aliases' => $company->aliases->pluck('name')->implode(', '),
        ]);
    }
}
