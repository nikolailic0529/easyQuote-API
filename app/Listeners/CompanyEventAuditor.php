<?php

namespace App\Listeners;

use App\Events\Company\CompanyCreated;
use App\Events\Company\CompanyDeleted;
use App\Events\Company\CompanyUpdated;
use App\Jobs\Opportunity\ValidateOpportunitiesOfCompany;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Services\Activity\ActivityLogger;
use App\Services\Activity\ChangesDetector;
use App\Services\Opportunity\ValidateOpportunityService;
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
                [self::class, 'auditUpdatedEvent'],
                [self::class, 'revalidateRelatedOpportunities'],
            ],
            CompanyDeleted::class => [
                [self::class, 'auditDeletedEvent'],
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

    protected function getAttributesToBeLogged(Company $company): array
    {
        $newAttributes = $this->changesDetector->getModelChanges($company, self::$logAttributes);

        return array_merge($newAttributes, [
            'status' => $company->status->name,
            'addresses' => $company->addresses->map(fn(Address $address) => "\{$address->address_representation\}")
                ->implode(', '),
            'contacts' => $company->contacts->map(fn(Contact $contact) => "\{$contact->contact_representation\}")
                ->implode(', '),
            'vendors' => $company->vendors->pluck('short_code')->implode(', '),
            'aliases' => $company->aliases->pluck('name')->implode(', '),
        ]);
    }
}