<?php

namespace App\Domain\Worldwide\Services\Opportunity;

use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\Company\Models\ImportedCompany;
use App\Domain\Company\Services\ImportedCompanyToPrimaryAccountProjector;
use App\Domain\Contact\Models\Contact;
use App\Domain\Contact\Models\ImportedContact;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\DataTransferObjects\Opportunity\BatchOpportunityUploadResult;
use App\Domain\Worldwide\DataTransferObjects\Opportunity\BatchSaveOpportunitiesData;
use App\Domain\Worldwide\DataTransferObjects\Opportunity\CreateSupplierData;
use App\Domain\Worldwide\DataTransferObjects\Opportunity\ImportedOpportunityData;
use App\Domain\Worldwide\DataTransferObjects\Opportunity\ImportFilesData;
use App\Domain\Worldwide\DataTransferObjects\Opportunity\PendingImportedOpportunityData;
use App\Domain\Worldwide\Events\Opportunity\OpportunityBatchFilesImported;
use App\Domain\Worldwide\Events\Opportunity\OpportunityCreated;
use App\Domain\Worldwide\Events\Opportunity\OpportunityUpdated;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\OpportunitySupplier;
use App\Domain\Worldwide\Services\Opportunity\Models\OpportunityLookupParameters;
use App\Foundation\Validation\Exceptions\ValidationException;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Factory as ValidatorFactory;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;

class OpportunityImportService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected ConnectionInterface $connection,
        protected ValidatorInterface $validator,
        protected EventDispatcher $eventDispatcher,
        protected ValidatorFactory $validatorFactory,
        protected OpportunityDataMapper $dataMapper,
        protected OpportunityLookupService $lookupService,
        protected ImportedCompanyToPrimaryAccountProjector $accountProjector,
    ) {
    }

    public function import(ImportFilesData $data): BatchOpportunityUploadResult
    {
        $opportunitiesDataFileReader = (new OpportunityBatchFileReader(
            $data->opportunities_file->getRealPath(),
            $data->opportunities_file->getClientOriginalExtension()
        ));

        $opportunitiesFileName = $data->opportunities_file->getClientOriginalName();

        $accountsDataFileReader = null;
        $accountContactsFileReader = null;

        if (!is_null($data->accounts_data_file)) {
            $accountsDataFileReader = AccountsDataBatchFileReader::fromUploadedFile($data->accounts_data_file);
        }

        if (!is_null($data->account_contacts_file)) {
            $accountContactsFileReader = AccountContactBatchFileReader::fromUploadedFile($data->account_contacts_file);
        }

        $accountsDataDictionary = [];
        $accountContactsDictionary = [];

        if (!is_null($accountsDataFileReader)) {
            $accountsDataDictionary = iterator_to_array($accountsDataFileReader->getRows());
        }

        if (!is_null($accountContactsFileReader)) {
            $accountContactsDictionary = value(static function () use ($accountContactsFileReader): array {
                $dictionary = [];

                foreach ($accountContactsFileReader->getRows() as $key => $row) {
                    $dictionary[$key][] = $row;
                }

                return $dictionary;
            });
        }

        $errors = (new MessageBag())
            ->setFormat('Validation failure on :key row. :message');

        $oppsCollection = Collection::empty();

        foreach ($opportunitiesDataFileReader->getRows() as $i => $row) {
            $rowFailures = (new ImportedOpportunityDataValidator($this->validatorFactory))(
                row: $row,
                accountsDataDictionary: $accountsDataDictionary,
                accountContactsDataDictionary: $accountContactsDictionary
            );

            if ($rowFailures->isNotEmpty()) {
                foreach ($rowFailures->all() as $error) {
                    $errors->add($i + 1, $error);
                }

                continue;
            }

            $oppsCollection[] = $this->performOpportunityImport(
                $this->dataMapper->mapImportedOpportunityDataFromImportedRow(
                    row: $row,
                    accountsDataDictionary: $accountsDataDictionary,
                    accountContactsDataDictionary: $accountContactsDictionary
                )
            );
        }

        $this->eventDispatcher->dispatch(
            new OpportunityBatchFilesImported(
                opportunitiesDataFile: $data->opportunities_file,
                accountsDataFile: $data->account_contacts_file,
                accountContactsFile: $data->account_contacts_file,
            )
        );

        $pendingOpps = $oppsCollection->loadMissing([
            'importedPrimaryAccount',
            'accountManager',
            'contractType',
        ]);

        return BatchOpportunityUploadResult::from([
            'opportunities' => $pendingOpps->all(),
            'errors' => $errors->all("File: [$opportunitiesFileName], Row :key: :message"),
        ]);
    }

    /**
     * @throws ValidationException
     * @throws \Throwable
     */
    public function performOpportunityImport(ImportedOpportunityData $data): Opportunity
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap(new Opportunity(), function (Opportunity $opportunity) use ($data): void {
            $opportunity->{$opportunity->getKeyName()} = (string) Uuid::generate(4);
            $opportunity->forceFill($data->except('create_suppliers')->toArray());
            $opportunity->{$opportunity->getDeletedAtColumn()} = $opportunity->freshTimestamp();

            if ($this->causer instanceof User) {
                $opportunity->owner()->associate($this->causer);
            }

            $opportunity->setRelation('opportunitySuppliers',
                (static function (CreateSupplierData ...$suppliers) use ($opportunity) {
                    return Collection::make($suppliers)
                        ->map(static function (CreateSupplierData $data) use ($opportunity): OpportunitySupplier {
                            /* @noinspection PhpIncompatibleReturnTypeInspection */
                            return (new OpportunitySupplier())
                                ->forceFill($data->toArray())
                                ->opportunity()
                                ->associate($opportunity)
                                ->unsetRelation('opportunity');
                        });
                })(...$data->create_suppliers));

            $this->connection->transaction(static function () use ($opportunity): void {
                $opportunity->save();
                $opportunity->opportunitySuppliers->each->save();
            });
        });
    }

    public function saveImported(BatchSaveOpportunitiesData $data): void
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        foreach ($data->opportunities as $opportunity) {
            $this->performOpportunitySave($opportunity);
        }
    }

    public function performOpportunitySave(Opportunity $opportunity): Opportunity
    {
        if (null !== $opportunity->importedPrimaryAccount) {
            $account = ($this->accountProjector)($opportunity->importedPrimaryAccount);

            $this->resolveRelationsForImportedAccount($opportunity->importedPrimaryAccount)
                ->each(static fn (string $rel) => $opportunity->$rel()->associate($account));

            $opportunity->primaryAccountContact()->associate($this->resolvePrimaryContactFromImportedContact(
                $opportunity->importedPrimaryAccountContact,
                $account->refresh()->contacts
            ));
        }

        $matchingOpportunity = $this->lookupService->find(OpportunityLookupParameters::from($opportunity));

        if (null !== $matchingOpportunity) {
            return tap($matchingOpportunity, function (Opportunity $matchingOpportunity) use ($opportunity): void {
                $oldOpportunity = (new Opportunity())->setRawAttributes($matchingOpportunity->getRawOriginal());

                $this->dataMapper->mergeAttributesFrom($matchingOpportunity, $opportunity);

                $this->connection->transaction(static fn () => $matchingOpportunity->save());

                $this->eventDispatcher->dispatch(
                    new OpportunityUpdated($opportunity, $oldOpportunity, $this->causer)
                );
            });
        }

        return tap($opportunity, function (Opportunity $opportunity): void {
            $this->connection->transaction(static fn () => $opportunity->restore());

            $opportunity->wasRecentlyCreated = true;

            $this->eventDispatcher->dispatch(
                new OpportunityCreated($opportunity, $this->causer)
            );
        });
    }

    private function resolveRelationsForImportedAccount(ImportedCompany $account): BaseCollection
    {
        $model = new Opportunity();
        $relations = [];

        if ((
            !$account->getFlag(ImportedCompany::IS_RESELLER)
            && !$account->getFlag(ImportedCompany::IS_END_USER)
        )
        || $account->getFlag(ImportedCompany::IS_RESELLER)
        ) {
            $relations[] = $model->primaryAccount()->getRelationName();
        }

        if ($account->flags === 0 || $account->getFlag(ImportedCompany::IS_RESELLER)) {
            $relations[] = $model->primaryAccount()->getRelationName();
        }

        if ($account->getFlag(ImportedCompany::IS_END_USER)) {
            $relations[] = $model->endUser()->getRelationName();
        }

        return collect($relations)->unique()->values();
    }

    private function resolvePrimaryContactFromImportedContact(?ImportedContact $contact, Collection $contacts): ?Contact
    {
        if (null === $contact) {
            return null;
        }

        return $contacts
            ->lazy()
            ->whereStrict('first_name', $contact->first_name)
            ->whereStrict('last_name', $contact->last_name)
            ->first();
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, function () use ($causer) {
            $this->causer = $causer;
            $this->accountProjector->setCauser($causer);
        });
    }
}
