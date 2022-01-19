<?php

namespace App\Services\Company;

use App\Contracts\LoggerAware;
use App\Enum\CompanyType;
use App\Models\Address;
use App\Models\Asset;
use App\Models\Attachable;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\CompanyNote;
use App\Models\Contact;
use App\Models\Customer\Customer;
use App\Models\Image;
use App\Models\Opportunity;
use App\Models\Vendor;
use App\Services\Company\Models\MergedCompanyRelations;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use JetBrains\PhpStorm\Pure;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DeduplicateCompaniesService implements LoggerAware
{
    final const DRY_RUN = 2;

    protected int $flags = 0;

    #[Pure]
    public function __construct(protected ConnectionInterface $connection,
                                protected LoggerInterface     $logger = new NullLogger())
    {
    }

    public function work(int $flags = 0): void
    {
        $this->flags = $flags;

        if ($this->runningInDryMode()) {
            $this->logger->warning("Running in dry-mode.");
        }

        $this->logger->info('Searching duplicated companies...');

        $companies = Company::query()
            ->where('type', CompanyType::EXTERNAL)
            ->where('is_system', false)
            ->distinct('name')
            ->select('name')
            ->toBase()
            ->cursor();

        $any = false;

        foreach ($companies as $company) {

            $instancesOfCompany = Company::query()
                ->where('name', $company->name)
                ->where('is_system', false)
                ->get();

            if ($instancesOfCompany->count() < 2) {
                continue;
            }

            $this->logger->info("Found {$instancesOfCompany->count()} instances of `$company->name` company.");

            $this->performDeduplicationOf($instancesOfCompany);

            $any = true;

        }

        if (!$any) {
            $this->logger->info('No duplicate has been found.');
        } else {
            $this->logger->info('Deduplication completed.');
        }

    }

    public function performDeduplicationOf(Collection $companies): void
    {
        // Looking for the best candidate for keeping.
        // Take the oldest companies first.
        $companies = $companies
            ->sortBy(static function (Company $company) {

                /** @var \DateTimeInterface $dt */
                $dt = $company->{$company->getCreatedAtColumn()};

                return $dt->getTimestamp();
            });

        /** @var Company $candidate */
        $candidate = $companies->shift();

        $this->logger->info("Chosen the candidate.", [
            'candidate' => $candidate->only('id', 'name', 'created_at'),
        ]);

        $this->logger->info('Merging relations from the duplicates.');
        /** @noinspection PhpParamsInspection */
        $mergedRelations = $this->mergeRelationsFromDuplicates($candidate, ...$companies);


        $this->logger->info('Merging attributes from the duplicates.');
        /** @noinspection PhpParamsInspection */
        $mergedAttributes = $this->mergeAttributesFromDuplicates($candidate, ...$companies);

        $this->logger->info('The candidate has been merged.', [
            'mergedRelations' => $mergedRelations->jsonSerialize(),
            'mergedAttributes' => $mergedAttributes,
        ]);

        $this->logger->info('Deleting the duplicates.', [
            'duplicates' => $companies->modelKeys(),
        ]);

        if (!$this->runningInDryMode()) {
            $this->connection->transaction(static function () use ($companies): void {
                $companies->each(static function (Company $company): void {
                    $company::withoutEvents(static fn() => $company->delete());
                });
            });
        }
    }

    private function mergeAttributesFromDuplicates(Company $candidate, Company ...$duplicates): array
    {
        $coalesceAttributes = [
            /**
             * @uses Company::$short_code
             * @uses Company::$vs_company_code
             * @uses Company::$vat
             * @uses Company::$vat_type
             * @uses Company::$is_system
             * @uses Company::$type
             * @uses Company::$category
             * @uses Company::$source
             * @uses Company::$email
             * @uses Company::$website
             * @uses Company::$user_id
             */
            'short_code',
            'vs_company_code',
            'vat',
            'vat_type',
            'is_system',
            'type',
            'category',
            'source',
            'email',
            'phone',
            'website',
            'user_id',
        ];

        collect($duplicates)->each(static function (Company $company) use ($candidate, $coalesceAttributes): void {

            foreach ($coalesceAttributes as $attr) {
                $candidate->{$attr} = coalesce_blank($candidate->{$attr}, $company->{$attr});
            }

        });

        if ($this->runningInDryMode()) {
            return $candidate->getDirty();
        }

        $this->connection->transaction(static fn() => $candidate->saveQuietly());

        return $candidate->getChanges();
    }

    private function mergeRelationsFromDuplicates(Company $candidate, Company ...$duplicates): MergedCompanyRelations
    {
        return tap(MergedCompanyRelations::new(), function (MergedCompanyRelations $merged) use ($candidate, $duplicates): void {

            collect($duplicates)->each(static function (Company $company) use ($merged): void {

                // Assets
                $merged->assets->push(...$company->assets()->get());

                // Attachments
                $merged->attachments->push(...$company->attachments()->get());

                // Opportunities
                $merged->opportunitiesWherePrimaryAccount->push(
                    ...Opportunity::query()->where('primary_account_id', $company->getKey())->get()
                );

                $merged->opportunitiesWhereEndUser->push(
                    ...Opportunity::query()->where('end_user_id', $company->getKey())->get()
                );

                // Company Notes
                $merged->notes->push(...$company->companyNotes()->get());

                // Image
                if (!is_null($company->image)) {
                    $merged->images->push($company->image);
                }

                // Vendors (merge missing from duplicate to candidate).
                $merged->vendors->push(...$company->vendors()->get());

                // Addresses
                $merged->addresses->push(...$company->addresses()->get());

                // Contacts
                $merged->contacts->push(...$company->contacts()->get());

                // Request for quotes
                $merged->requestsForQuote->push(
                    ...Customer::query()->where('company_reference_id', $company->getKey())->get()
                );

            });

            if ($this->runningInDryMode()) {
                return;
            }

            $this->connection->transaction(static function () use ($merged, $candidate) {

                $merged->assets->each(static function (Asset $asset) use ($candidate): void {
                    $asset->companies()->syncWithoutDetaching($candidate);
                });

                $merged->attachments->each(static function (Attachment $attachment) use ($candidate): void {

                    tap(new Attachable(), static function (Attachable $attachable) use ($attachment, $candidate): void {
                        $attachable->related()->associate($candidate);
                        $attachable->attachment_id = $attachment->getKey();

                        $attachable->saveQuietly();
                    });

                });

                $merged->opportunitiesWherePrimaryAccount->each(static function (Opportunity $opportunity) use ($candidate): void {

                    $opportunity->primaryAccount()->associate($candidate);

                    $opportunity->saveQuietly();

                });

                $merged->opportunitiesWhereEndUser->each(static function (Opportunity $opportunity) use ($candidate): void {

                    $opportunity->endUser()->associate($candidate);

                    $opportunity->saveQuietly();

                });

                $merged->notes->each(static function (CompanyNote $note) use ($candidate): void {

                    $note->company()->associate($candidate);

                    $note->saveQuietly();

                });

                with($merged->images->first(), static function (?Image $image) use ($candidate): void {

                    if (is_null($image)) {
                        return;
                    }

                    if (!is_null($candidate->image)) {
                        return;
                    }

                    $image->imageable()->associate($candidate);

                    $image->saveQuietly();

                });

                $merged->vendors->each(static function (Vendor $vendor) use ($candidate): void {

                    $candidate->vendors()->syncWithoutDetaching($vendor);

                });

                $merged->addresses->each(static function (Address $address) use ($candidate): void {

                    $candidate->addresses()->syncWithoutDetaching($address);

                });

                $merged->contacts->each(static function (Contact $contact) use ($candidate): void {

                    $candidate->contacts()->syncWithoutDetaching($contact);

                });

                $merged->requestsForQuote->each(static function (Customer $customer) use ($candidate): void {

                    $customer->referencedCompany()->associate($candidate);

                    $customer->saveQuietly();

                });

            });


        });

    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn() => $this->logger = $logger);
    }

    public function runningInDryMode(): bool
    {
        return (bool)($this->flags & self::DRY_RUN);
    }
}