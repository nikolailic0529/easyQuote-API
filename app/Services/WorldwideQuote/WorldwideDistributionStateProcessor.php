<?php

namespace App\Services\WorldwideQuote;

use App\Contracts\Services\{ManagesDocumentProcessors, ManagesExchangeRates, ProcessesWorldwideDistributionState};
use App\DTO\{Discounts\DistributionDiscountsCollection,
    DistributionDetailsCollection,
    DistributionExpiryDateCollection,
    DistributionMapping,
    DistributionMappingCollection,
    DistributionMarginTaxCollection,
    MappedRow\UpdateMappedRowFieldCollection,
    MappedRowSettings,
    ProcessableDistribution,
    ProcessableDistributionCollection,
    RowMapping,
    RowsGroupData,
    SelectedDistributionRows,
    SelectedDistributionRowsCollection};
use App\Enum\Lock;
use App\Events\DistributionProcessed;
use App\Models\{Address,
    Contact,
    Data\Country,
    OpportunitySupplier,
    Quote\DistributionFieldColumn,
    Quote\WorldwideDistribution,
    Quote\WorldwideQuoteVersion,
    QuoteFile\DistributionRowsGroup,
    QuoteFile\MappedRow,
    QuoteFile\QuoteFile,
    QuoteFile\ScheduleData,
    Template\TemplateField};
use App\Process\ProcessPool\ProcessPool;
use App\Queries\WorldwideDistributionQueries;
use App\Services\QuoteFileService;
use Illuminate\Contracts\{Cache\Lock as LockContract,
    Cache\LockProvider,
    Config\Repository as Config,
    Events\Dispatcher,
    Filesystem\Filesystem};
use Illuminate\Database\{ConnectionInterface,
    Eloquent\Builder,
    Eloquent\Collection,
    Eloquent\ModelNotFoundException,
    Eloquent\Relations\MorphToMany};
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\{Arr, Carbon, Collection as BaseCollection, Facades\App, Facades\Storage, MessageBag, Str};
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\{Process\Process,
    Validator\Constraints,
    Validator\Exception\ValidationFailedException,
    Validator\Validator\ValidatorInterface};
use Throwable;
use Webpatser\Uuid\Uuid;
use function base_path;
use function blank;
use function collect;
use function now;
use function tap;
use function transform;
use function value;
use function with;

class WorldwideDistributionStateProcessor implements ProcessesWorldwideDistributionState
{
    protected LoggerInterface $logger;

    protected ConnectionInterface $connection;

    protected LockProvider $lockProvider;

    /** @var Filesystem|FilesystemAdapter */
    protected Filesystem $storage;

    protected Config $config;

    protected Dispatcher $dispatcher;

    protected ValidatorInterface $validator;

    protected ManagesDocumentProcessors $documentProcessor;

    protected ManagesExchangeRates $exchangeRateService;

    protected WorldwideDistributionQueries $distributionQueries;

    public function __construct(
        LoggerInterface $logger,
        ConnectionInterface $connection,
        LockProvider $lockProvider,
        Filesystem $storage,
        Config $config,
        Dispatcher $dispatcher,
        ValidatorInterface $validator,
        ManagesDocumentProcessors $documentProcessor,
        ManagesExchangeRates $exchangeRateService,
        WorldwideDistributionQueries $distributionQueries
    )
    {
        $this->logger = $logger;
        $this->connection = $connection;
        $this->lockProvider = $lockProvider;
        $this->storage = $storage;
        $this->config = $config;
        $this->dispatcher = $dispatcher;
        $this->validator = $validator;
        $this->documentProcessor = $documentProcessor;
        $this->exchangeRateService = $exchangeRateService;
        $this->distributionQueries = $distributionQueries;
    }

    public function initializeDistribution(WorldwideQuoteVersion $quote, ?string $opportunitySupplierId = null): WorldwideDistribution
    {
        /** @var WorldwideDistribution $wwDistribution */

        $constraints = new Constraints\Collection([
            'worldwideQuoteId' => new Constraints\Uuid(),
            'opportunitySupplierId' => new Constraints\Uuid()
        ]);

        $violations = $this->validator->validate($payload = [
            'worldwideQuoteId' => $quote->getKey(),
            'opportunitySupplierId' => $opportunitySupplierId
        ], $constraints);

        if (count($violations)) {
            throw new ValidationFailedException($payload, $violations);
        }

        /** @var OpportunitySupplier|null $supplier */
        $supplier = transform($opportunitySupplierId, function (string $supplierModelKey) {
            return OpportunitySupplier::query()->find($supplierModelKey);
        });

        return tap(new WorldwideDistribution(), function (WorldwideDistribution $distribution) use ($quote, $supplier) {
            $distribution->worldwideQuote()->associate($quote);
            $distribution->opportunitySupplier()->associate($supplier);

            if (!is_null($supplier)) {
                $distribution->country()->associate(
                    Country::query()->where('name', $supplier->country_name)->first()
                );
            }

            $templateFields = TemplateField::query()
                ->where('is_system', true)
                ->whereIn('name', $this->config->get('quote-mapping.worldwide_quote.fields', []))
                ->pluck('id');

            $this->connection->transaction(function () use ($templateFields, $distribution) {
                $distribution->save();
                $distribution->templateFields()->sync($templateFields);
            });
        });
    }

    public function syncDistributionWithOwnOpportunitySupplier(WorldwideDistribution $distributorQuote): void
    {
        $supplier = $distributorQuote->opportunitySupplier;

        if (is_null($supplier)) {
            return;
        }

        $opportunity = $supplier->opportunity;

        $distributorQuote->country()->associate($supplier->country);

//        $newAddressModelsOfDistributorQuote = [];
//        $newContactModelsOfDistributorQuote = [];
        $addressPivotsOfDistributorQuote = [];
        $contactPivotsOfDistributorQuote = [];

        /**
         * If primary account is present on opportunity entity,
         * we replicate only new address, contact entities to the distributor quote.
         */
        if (!is_null($opportunity->primaryAccount)) {
            $opportunity->primaryAccount->load(['addresses' => function (MorphToMany $relation) {
                $relation->wherePivot('is_default', true);
            }]);

            $opportunity->primaryAccount->load(['contacts' => function (MorphToMany $relation) {
                $relation->wherePivot('is_default', true);
            }]);

            $addressPivotsOfDistributorQuote = $opportunity->primaryAccount->addresses->mapWithKeys(function (Address $address) {
                return [$address->getKey() => ['is_default' => $address->pivot->is_default]];
            })
                ->all();

            $contactPivotsOfDistributorQuote = $opportunity->primaryAccount->contacts->mapWithKeys(function (Contact $contact) {
                return [$contact->getKey() => ['is_default' => $contact->pivot->is_default]];
            })
                ->all();
        }

        $lock = $this->lockProvider->lock(Lock::UPDATE_WWDISTRIBUTION($distributorQuote->getKey()), 10);

        $lock->block(
            30,
            fn() => $this->connection->transaction(function () use ($distributorQuote, $addressPivotsOfDistributorQuote, $contactPivotsOfDistributorQuote) {
                $distributorQuote->save();

                if (!empty($addressPivotsOfDistributorQuote)) {
                    $distributorQuote->addresses()->syncWithoutDetaching($addressPivotsOfDistributorQuote);
                }

                if (!empty($contactPivotsOfDistributorQuote)) {
                    $distributorQuote->contacts()->syncWithoutDetaching($contactPivotsOfDistributorQuote);
                }

            })
        );
    }

    public function applyDistributionsDiscount(WorldwideQuoteVersion $quote, DistributionDiscountsCollection $collection)
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        foreach ($collection as $discountsData) {
            $violations = $this->validator->validate($discountsData);

            if (count($violations)) {
                throw new ValidationFailedException($discountsData, $violations);
            }
        }

        $collection->rewind();

        if ($newVersionResolved) {
            foreach ($collection as $discountsData) {
                $originalModel = $discountsData->worldwideDistribution;

                /** @var WorldwideDistribution $versionedModel */
                $versionedModel = WorldwideDistribution::query()
                    ->where('replicated_distributor_quote_id', $originalModel->getKey())
                    ->where('worldwide_quote_id', $quote->getKey())
                    ->sole();

                $discountsData->worldwideDistribution = $versionedModel;
            }
        }

        foreach ($collection as $discountsData) {
            $model = $discountsData->worldwideDistribution;

            if (!is_null($discountsData->predefinedDiscounts)) {
                $model->multiYearDiscount()->associate($discountsData->predefinedDiscounts->multiYearDiscount);
                $model->prePayDiscount()->associate($discountsData->predefinedDiscounts->prePayDiscount);
                $model->promotionalDiscount()->associate($discountsData->predefinedDiscounts->promotionalDiscount);
                $model->snDiscount()->associate($discountsData->predefinedDiscounts->snDiscount);
            } else {
                $model->multiYearDiscount()->dissociate();
                $model->prePayDiscount()->dissociate();
                $model->promotionalDiscount()->dissociate();
                $model->snDiscount()->dissociate();
            }

            $model->custom_discount = $discountsData->customDiscount;

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWDISTRIBUTION($discountsData->worldwideDistribution->getKey()),
                10
            );

            $lock->block(30, function () use ($model) {
                $this->connection->transaction(fn() => $model->save());
            });
        }
    }

    public function setDistributionsExpiryDate(DistributionExpiryDateCollection $collection)
    {
        foreach ($collection as $distribution) {
            $violations = $this->validator->validate($distribution);

            if (count($violations)) {
                throw new ValidationFailedException($distribution, $violations);
            }
        }

        $collection->rewind();

        $modelKeys = array_unique(Arr::pluck($collection, 'worldwide_distribution_id'));

        /** @var Collection<WorldwideDistribution>|WorldwideDistribution[] */
        $distributions = WorldwideDistribution::query()
            ->whereKey($modelKeys)
            ->get(['id', 'worldwide_quote_id', 'distribution_expiry_date', 'created_at', 'updated_at'])
            ->keyBy('id');

        $missingDistributions = array_diff($modelKeys, $distributions->modelKeys());

        if (!empty($missingDistributions)) {
            throw (new ModelNotFoundException)->setModel(WorldwideDistribution::class, $missingDistributions);
        }

        foreach ($collection as $distributionData) {
            /** @var WorldwideDistribution $model */
            $model = $distributions[$distributionData->worldwide_distribution_id];

            $model->distribution_expiry_date = $distributionData->distribution_expiry_date->toDateString();

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWDISTRIBUTION($model->getKey()),
                10
            );

            $lock->block(30, function () use ($model) {
                $this->connection->transaction(fn() => $model->save());
            });
        }
    }

    public function updateDistributionsDetails(WorldwideQuoteVersion $quote, DistributionDetailsCollection $collection)
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        foreach ($collection as $detailsData) {
            $violations = $this->validator->validate($detailsData);

            if (count($violations)) {
                throw new ValidationFailedException($detailsData, $violations);
            }
        }

        $collection->rewind();

        if ($newVersionResolved) {
            foreach ($collection as $detailsData) {
                /** @var WorldwideDistribution $versionedModel */
                $versionedModel = $quote->worldwideDistributions()->where('replicated_distributor_quote_id', $detailsData->worldwide_distribution->getKey())->sole();

                $detailsData->worldwide_distribution = $versionedModel;
            }
        }

        foreach ($collection as $detailsData) {
            $model = $detailsData->worldwide_distribution;

            $model->pricing_document = $detailsData->pricing_document;
            $model->service_agreement_id = $detailsData->service_agreement_id;
            $model->system_handle = $detailsData->system_handle;
            $model->purchase_order_number = $detailsData->purchase_order_number;
            $model->vat_number = $detailsData->vat_number;
            $model->additional_details = $detailsData->additional_details;

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWDISTRIBUTION($model->getKey()),
                10
            );

            $lock->block(30, function () use ($model) {
                $this->connection->transaction(fn() => $model->save());
            });
        }
    }

    public function processDistributionsImport(WorldwideQuoteVersion $quote, ProcessableDistributionCollection $collection)
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        foreach ($collection as $distribution) {
            $violations = $this->validator->validate($distribution);

            if (count($violations)) {
                throw new ValidationFailedException($distribution, $violations);
            }
        }

        $collection->rewind();

        $modelKeys = collect($collection)->pluck('id');
        $fileKeys = collect($collection)->reduce(function (BaseCollection $keys, ProcessableDistribution $distribution) {
            $keys->push($distribution->distributor_file_id);

            if (null !== $distribution->schedule_file_id) {
                $keys->push($distribution->schedule_file_id);
            }

            return $keys;
        }, BaseCollection::make());

        /** @var Collection<WorldwideDistribution>|WorldwideDistribution[] $distributions */
        $distributions = $quote->worldwideDistributions()
            ->when($newVersionResolved, function (Builder $builder) use ($modelKeys) {
                $builder->whereIn('replicated_distributor_quote_id', $modelKeys);
            }, function (Builder $builder) use ($modelKeys) {
                $builder->whereKey($modelKeys);
            })
            ->get();

        $distributions = value(function () use ($newVersionResolved, $distributions): Collection {
            if ($newVersionResolved) {
                return $distributions->keyBy('replicated_distributor_quote_id');
            }

            return $distributions->keyBy('id');
        });

        /** @var Collection */
        $quoteFiles = $distributions
            ->load(['distributorFile', 'scheduleFile'])
            ->reduce(function (array $quoteFileCollection, WorldwideDistribution $distributorQuote) use ($newVersionResolved) {
                $distributorQuoteFiles = collect([
                    $distributorQuote->distributorFile,
                    $distributorQuote->scheduleFile
                ])->filter()->values();

                if ($newVersionResolved) {
                    return array_merge($quoteFileCollection, $distributorQuoteFiles->keyBy('replicated_quote_file_id')->all());
                }

                return array_merge($quoteFileCollection, $distributorQuoteFiles->keyBy('id')->all());
            }, []);

        $quoteFiles = new Collection($quoteFiles);

        $missingDistributions = $modelKeys->diff($distributions->keys());
        $missingQuoteFiles = $fileKeys->diff($quoteFiles->keys());

        if ($missingDistributions->isNotEmpty()) {
            throw (new ModelNotFoundException)->setModel(WorldwideDistribution::class, $missingDistributions->all());
        }

        if ($missingQuoteFiles->isNotEmpty()) {
            throw (new ModelNotFoundException)->setModel(QuoteFile::class, $missingDistributions->all());
        }

        $fileLocks = $quoteFiles->map(function (QuoteFile $file) {
            return tap($this->lockProvider->lock(Lock::UPDATE_QUOTE_FILE($file->getKey()), 10), function (LockContract $lock) {
                $lock->block(30);
            });
        });

        $distributionLocks = $distributions->map(function (WorldwideDistribution $distribution) {
            return tap($this->lockProvider->lock(Lock::UPDATE_WWDISTRIBUTION($distribution->getKey()), 10), function (LockContract $lock) {
                $lock->block(30);
            });
        });

        $this->connection->beginTransaction();

        /** @var Process[] */
        $processes = [];

        try {
            foreach ($collection as $distribution) {
                /** @var WorldwideDistribution $model */
                $model = $distributions->get($distribution->id);

                $distributorFileModel = $quoteFiles->get($distribution->distributor_file_id);
                $scheduleFileModel = $quoteFiles->get($distribution->schedule_file_id);

                if ($newVersionResolved) {
                    $distribution->id = $model->getKey();
                    $distribution->distributor_file_id = (string)$model->distributor_file_id;
                    $distribution->schedule_file_id = $model->schedule_file_id;
                }

                tap($distributorFileModel, function (QuoteFile $file) use ($distribution) {
                    $file->forceFill(['imported_page' => $distribution->distributor_file_page ?? 1])->save();
                });

                if (!is_null($scheduleFileModel)) {
                    tap($scheduleFileModel, function (QuoteFile $file) use ($distribution) {
                        $file->forceFill(['imported_page' => $distribution->schedule_file_page ?? $file->pages])->save();
                    });
                } else {
                    $model->scheduleFile()->dissociate();
                }

                with($model, function (WorldwideDistribution $model) use ($distribution) {

                    $model->country()->associate($distribution->country_id);
                    $model->distributionCurrency()->associate($distribution->distribution_currency_id);
                    $model->buyCurrency()->associate($distribution->buy_currency_id);
                    $model->buy_price = $distribution->buy_price;
                    $model->calculate_list_price = $distribution->calculate_list_price;
                    $model->distribution_expiry_date = $distribution->distribution_expiry_date;

                    $model->save();

                    $model->vendors()->sync($distribution->vendors);
                    $model->addresses()->sync($distribution->address_ids);
                    $model->contacts()->sync($distribution->contact_ids);

                });

                $processes[] = $this->compileDistributionImportCommand($model);
            }

            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollback();

            throw $e;
        } finally {
            $fileLocks->each(function (LockContract $lock) {
                $lock->release();
            });

            $distributionLocks->each(function (LockContract $lock) {
                $lock->release();
            });
        }

        if (App::runningUnitTests()) {
            foreach ($distributions as $distribution) {
                $this->processSingleDistributionImport($distribution->getKey());
            }

            return;
        }

        $processIterator = function () use ($processes): \Iterator {
            foreach ($processes as $process) {
                yield $process;
            }
        };

        (new ProcessPool($processIterator()))
            ->setConcurrency(4)
            ->wait();
    }

    protected function compileDistributionImportCommand(WorldwideDistribution $worldwideDistribution): Process
    {
        return (new Process(['php', 'artisan', 'eq:process-ww-distribution', $worldwideDistribution->getKey()]))
            ->setWorkingDirectory(base_path());
    }

    public function processSingleDistributionImport(string $distributionId)
    {
        $this->logger->info(
            "Try process Worldwide Distribution '$distributionId'..."
        );

        /** @var WorldwideDistribution $wwDistribution */
        $wwDistribution = WorldwideDistribution::query()->findOrFail($distributionId, ['id', 'replicated_distributor_quote_id', 'worldwide_quote_id', 'worldwide_quote_type', 'distributor_file_id', 'schedule_file_id']);

        $wwDistribution->templateFields()->sync(TemplateField::query()
            ->whereIn('name', $this->config['quote-mapping.worldwide_quote.fields'] ?? [])
            ->where('is_system', true)->pluck('id'));

        $failures = new MessageBag(['distributor_file' => [], 'schedule_file' => []]);

        tap($wwDistribution->distributorFile, function (?QuoteFile $file) use ($wwDistribution, $failures) {
            if ($file === null) {
                $this->logger->info("No Distributor File found.", [$wwDistribution->getForeignKey() => $wwDistribution->getKey()]);

                return;
            }

            try {
                $this->documentProcessor->forwardProcessor($file);

                $this->logger->info("Distributor File '$file->original_file_name' has been processed.", [$wwDistribution->getForeignKey() => $wwDistribution->getKey()]);

                if ($file->rowsData()->getBaseQuery()->doesntExist()) {
                    $failures->add('distributor_file', 'No data found in the file.');
                } else {
                    $this->guessDistributionMapping($wwDistribution);
                }

                //
            } catch (Throwable $e) {
                report($e);

                $failures->add('distributor_file', 'A system error occurred. Please contact with administrator.');
            }
        });

        tap($wwDistribution->scheduleFile, function (?QuoteFile $file) use ($wwDistribution, $failures) {
            if ($file === null) {
                $this->logger->info("No Payment Schedule File found.", [$wwDistribution->getForeignKey() => $wwDistribution->getKey()]);

                return;
            }

            try {
                $this->documentProcessor->forwardProcessor($file);

                $this->logger->info("Payment Schedule File '$file->original_file_name' has been processed.", [$wwDistribution->getForeignKey() => $wwDistribution->getKey()]);

                if (is_null($file->scheduleData) || BaseCollection::wrap($file->scheduleData->value)->isEmpty()) {
                    $failures->add('schedule_file', 'No data found in the file');
                }
            } catch (Throwable $e) {
                report($e);

                $failures->add('schedule_file', 'A system error occurred. Please contact with administrator.');
            }
        });

        $lock = $this->lockProvider->lock(Lock::UPDATE_WWDISTRIBUTION($wwDistribution->getKey()), 10);

        $wwDistribution->imported_at = now();

        $lock->block(30, function () use ($wwDistribution) {

            $this->connection->transaction(fn() => $wwDistribution->save());

        });

        $parentOfDistributorQuote = $wwDistribution->worldwideQuote;

        $this->dispatcher->dispatch(new DistributionProcessed(
            $parentOfDistributorQuote->{$parentOfDistributorQuote->worldwideQuote()->getForeignKeyName()},
            $wwDistribution->replicated_distributor_quote_id ?? $wwDistribution->getKey(),
            $failures
        ));
    }

    protected function guessDistributionMapping(WorldwideDistribution $worldwideDistribution): void
    {
        $mappingRow = $worldwideDistribution->mappingRow;

        if (is_null($mappingRow) || is_null($mappingRow->columns_data)) {
            return;
        }

        $importableColumnKeys = $mappingRow->columns_data->pluck('importable_column_id')->all();

        /** @var Collection<DistributionFieldColumn>|DistributionFieldColumn[] $possibleMappings */
        $possibleMappings = DistributionFieldColumn::query()
            ->whereIn('importable_column_id', $importableColumnKeys)
            ->whereNotNull('importable_column_id')
            ->groupBy('template_field_id', 'importable_column_id')
            ->orderByRaw('count(*) desc')
            ->select('template_field_id', 'importable_column_id')
            ->get();

        $guessedMapping = [];

        foreach ($importableColumnKeys as $columnKey) {
            /** @var DistributionFieldColumn|null $possibleMapping */
            $possibleMapping = $possibleMappings->first(function (DistributionFieldColumn $columnMapping) use ($columnKey) {
                return $columnMapping->importable_column_id === $columnKey;
            });

            if (!is_null($possibleMapping)) {
                $guessedMapping[$possibleMapping->template_field_id] = [
                    'importable_column_id' => $columnKey
                ];
            }
        }

        if (empty($guessedMapping)) {
            return;
        }

        $lock = $this->lockProvider->lock(Lock::UPDATE_WWDISTRIBUTION($worldwideDistribution->getKey()), 10);

        $lock->block(30, function () use ($worldwideDistribution, $guessedMapping) {

            $this->connection->transaction(fn() => $worldwideDistribution->templateFields()->syncWithoutDetaching($guessedMapping));

        });
    }

    public function validateDistributionsAfterImport(ProcessableDistributionCollection $collection): MessageBag
    {
        $collection->rewind();

        $modelKeys = [];

        foreach ($collection as $distribution) {
            $modelKeys[] = $distribution->id;
        }

        /** @var Collection<WorldwideDistribution>|WorldwideDistribution[] $distributionModels */
        $distributionModels = WorldwideDistribution::query()
            ->whereKey($modelKeys)
            ->with('distributorFile', 'scheduleFile')
            ->get(['id', 'worldwide_quote_id', 'distributor_file_id', 'schedule_file_id']);

        $getDistributionName = function (WorldwideDistribution $distribution) {
            $distributionName = $this->distributionQueries->distributionQualifiedNameQuery($distribution->getKey(), $as = 'qualified_distribution_name')->value($as);

            if (blank($distributionName)) {
                return $distribution->getKey();
            }

            return $distributionName;
        };

        $errors = new MessageBag();

        foreach ($distributionModels as $distribution) {
            $distributionName = $getDistributionName($distribution);

            if (is_null($distribution->distributorFile)) {
                $errors->add($distributionName, 'No Distributor File uploaded.');
            }

            if ($distribution->distributorFile->rowsData()->getBaseQuery()->doesntExist()) {
                $fileName = $distribution->distributorFile->original_file_name;

                $errors->add($distributionName, "No Rows found in the Distributor File '$fileName'.");
            }

            if (!is_null($distribution->scheduleFile)) {
                with($distribution->scheduleFile->scheduleData, function (?ScheduleData $scheduleData) use ($distribution, $distributionName, $errors) {
                    $fileName = $distribution->scheduleFile->original_file_name;

                    if (is_null($scheduleData)) {
                        $errors->add($distributionName, "No Payment Data found in the Payment Schedule File '$fileName'.");

                        return;
                    }

                    $scheduleDataValue = $scheduleData->value;

                    if (empty($scheduleDataValue)) {
                        $errors->add($distributionName, "No Payment Data found in the Payment Schedule File '$fileName'.");
                    }
                });
            }

        }

        return $errors;
    }

    public function processDistributionsMapping(WorldwideQuoteVersion $quote, DistributionMappingCollection $collection)
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        foreach ($collection as $mapping) {
            $violations = $this->validator->validate($mapping);

            if (count($violations)) {
                throw new ValidationFailedException($mapping, $violations);
            }
        }

        $modelKeys = collect($collection)->pluck('worldwide_distribution_id')->unique();

        /** @var Collection<WorldwideDistribution>|WorldwideDistribution[] $distributions */
        $distributions = $quote->worldwideDistributions()
            ->when($newVersionResolved, function (Builder $builder) use ($modelKeys) {
                $builder->whereIn('replicated_distributor_quote_id', $modelKeys);
            }, function (Builder $builder) use ($modelKeys) {
                $builder->whereKey($modelKeys);
            })
            ->with('worldwideQuote')
            ->get(['id', 'replicated_distributor_quote_id', 'worldwide_quote_id', 'worldwide_quote_type', 'distributor_file_id', 'distribution_currency_id', 'created_at', 'updated_at']);

        $actualDistributorQuoteModelKeys = value(function () use ($distributions, $newVersionResolved): array {
            if ($newVersionResolved) {
                return $distributions->pluck('replicated_distributor_quote_id')->all();
            }

            return $distributions->modelKeys();
        });

        $missingDistributions = $modelKeys->diff($actualDistributorQuoteModelKeys);

        if ($missingDistributions->isNotEmpty()) {
            throw (new ModelNotFoundException)->setModel(WorldwideDistribution::class, $missingDistributions->all());
        }

        if ($distributions->unique('worldwide_quote_id')->count() > 1) {
            throw new RuntimeException("Worldwide Distributions must belong to the same Worldwide Quote");
        }

        $mapping = collect($collection)->groupBy(fn(DistributionMapping $mapping) => $mapping->worldwide_distribution_id);

        foreach ($distributions as $model) {
            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWDISTRIBUTION($model->getKey()),
                10
            );

            $lock->block(30);

            $this->connection->beginTransaction();

            try {
                /** @var BaseCollection */
                $distributionMapping = value(function () use ($model, $mapping, $newVersionResolved): BaseCollection {
                    if ($newVersionResolved) {
                        return $mapping->get($model->replicated_distributor_quote_id);
                    }
                    return $mapping->get($model->getKey());
                });

                // Filter the mapping related to the distribution.
                $modelMapping = BaseCollection::wrap($distributionMapping)->mapWithKeys(
                    fn(DistributionMapping $mapping) => [
                        $mapping->template_field_id => $mapping->except('worldwide_distribution_id', 'template_field_id')->toArray(),
                    ]
                )->all();

                if ($model->templateFields()->doesntExist()) {
                    $model->templateFields()->sync(
                        TemplateField::query()
                            ->where('is_system', true)
                            ->whereIn('name', $this->config->get('quote-mapping.worldwide_quote.fields', []))
                            ->pluck('id')
                    );
                }

                $originalModelMapping = DistributionFieldColumn::query()->where('worldwide_distribution_id', $model->getKey())->get();

                with($model->templateFields()->syncWithoutDetaching($modelMapping), function (array $changes) use ($model, $distributionMapping, $originalModelMapping) {
                    if (is_null($model->distributorFile)) {
                        return;
                    }

                    $newModelMapping = DistributionFieldColumn::query()->where('worldwide_distribution_id', $model->getKey())->get();

                    $mappingChanges = $this->compareOriginalMappingWithNew($originalModelMapping, $newModelMapping);

                    $columnChangesOfMapping = array_values(array_filter(Arr::pluck($mappingChanges, 'importable_column_id')));

                    // Perform imported rows mapping only when any mapping field is changed
                    // or the mapped rows are not created for the distribution yet.
                    if (empty($columnChangesOfMapping) && $model->mappedRows()->exists()) {
                        return;
                    }

                    $mappedRowDefaults = $this->getMappedRowSettings($model);

                    $rowMapping = $this->transitDistributionMappingToRowMapping(new DistributionMappingCollection($distributionMapping->all()));

                    $this->documentProcessor->transitImportedRowsToMappedRows($model->distributorFile, $rowMapping, $mappedRowDefaults);
                });

                $this->connection->commit();
            } catch (Throwable $e) {
                $this->connection->rollBack();

                throw $e;
            } finally {
                $lock->release();
            }
        }
    }

    private function compareOriginalMappingWithNew(Collection $originalMapping, Collection $newMapping): array
    {
        $changes = [];

        $originalMappingDictionary = $originalMapping->keyBy('template_field_id')->all();
        $newMappingDictionary = $newMapping->keyBy('template_field_id')->all();

        $pairIterator = function (array $originalMappingDictionary, array $newMappingDictionary): \Generator {
            foreach ($originalMappingDictionary as $key => $value) {
                yield $key => [$value, $newMappingDictionary[$key]];
            }
        };

        $comparingAttributes = [
            'importable_column_id',
            'is_default_enabled',
            'is_preview_visible',
            'is_editable',
            'sort'
        ];

        foreach ($pairIterator($originalMappingDictionary, $newMappingDictionary) as $key => $pair) {
            /** @var DistributionFieldColumn $originalColumnMapping */
            /** @var DistributionFieldColumn $newColumnMapping */

            [$originalColumnMapping, $newColumnMapping] = $pair;

            foreach ($comparingAttributes as $attribute) {

                if ($originalColumnMapping->{$attribute} <> $newColumnMapping->{$attribute}) {
                    $changes[$key][$attribute] = [
                        'original' => $originalColumnMapping->{$attribute},
                        'new' => $newColumnMapping->{$attribute}
                    ];
                }

            }
        }

        return $changes;
    }

    protected function getMappedRowSettings(WorldwideDistribution $worldwideDistribution): MappedRowSettings
    {
        $version = $worldwideDistribution->worldwideQuote;

        $exchangeRateValue = $this->exchangeRateService->getTargetRate(
            $worldwideDistribution->getRelationValue('distributionCurrency') ?? $version->quoteCurrency,
            $version->quoteCurrency
        );

        return new MappedRowSettings([
            'default_date_from' => transform($version->worldwideQuote->opportunity->opportunity_start_date, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date)),
            'default_date_to' => transform($version->worldwideQuote->opportunity->opportunity_end_date, fn(string $date) => Carbon::createFromFormat('Y-m-d', $date)),
            'default_qty' => 1,
            'calculate_list_price' => (bool)$worldwideDistribution->calculate_list_price,
            'exchange_rate_value' => $exchangeRateValue,
        ]);
    }

    protected function transitDistributionMappingToRowMapping(DistributionMappingCollection $distributionMapping): RowMapping
    {
        $mapping = Arr::pluck($distributionMapping, 'importable_column_id', 'template_field_id');

        $templateFields = TemplateField::query()->whereKey(array_keys($mapping))->pluck('name', 'id');

        $rowMapping = [];

        foreach ($templateFields as $key => $name) {
            $rowMapping[$name] = $mapping[$key] ?? null;
        }

        return new RowMapping($rowMapping);
    }

    public function updateRowsSelection(WorldwideQuoteVersion $quote, SelectedDistributionRowsCollection $collection)
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        foreach ($collection as $selection) {
            $violations = $this->validator->validate($collection);

            if (count($violations)) {
                throw new ValidationFailedException($selection, $violations);
            }
        }

        $modelKeys = Arr::pluck($collection, 'worldwide_distribution_id');

        /** @var Collection<WorldwideDistribution> */
        $distributions = $quote->worldwideDistributions()
            ->when($newVersionResolved, function (Builder $builder) use ($modelKeys) {
                $builder->whereIn('replicated_distributor_quote_id', $modelKeys);
            }, function (Builder $builder) use ($modelKeys) {
                $builder->whereKey($modelKeys);
            })
            ->get(['id', 'replicated_distributor_quote_id', 'distributor_file_id', 'created_at', 'updated_at']);

        /** @var Collection $distributions */
        $distributions = value(function () use ($distributions, $newVersionResolved): Collection {
            if ($newVersionResolved) {
                return $distributions->keyBy('replicated_distributor_quote_id');
            }

            return $distributions->keyBy('id');
        });

        $missingModelKeys = array_diff($modelKeys, $distributions->keys()->all());

        if (!empty($missingModelKeys)) {
            throw (new ModelNotFoundException())->setModel(WorldwideDistribution::class, array_values($missingModelKeys));
        }

        foreach ($collection as $selection) {
            /** @var WorldwideDistribution */
            $model = $distributions->get($selection->worldwide_distribution_id);

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWDISTRIBUTION($model->getKey()),
                10
            );

            $lock->block(30);

            $this->connection->beginTransaction();

            try {
                with($selection, function (SelectedDistributionRows $selection) use ($newVersionResolved, $model) {
                    if ($selection->use_groups) {
                        $model->use_groups = true;
                        $model->sort_rows_groups_column = $selection->sort_rows_groups_column;
                        $model->sort_rows_groups_direction = $selection->sort_rows_groups_direction;

                        $model->rowsGroups()->update(['is_selected' => false]);

                        $model->rowsGroups()->getQuery()
                            ->when(
                                $selection->reject,

                                function (Builder $builder) use ($selection, $newVersionResolved) {
                                    if ($newVersionResolved) {
                                        return $builder->whereNotIn('replicated_rows_group_id', $selection->selected_groups);
                                    }

                                    return $builder->whereKeyNot($selection->selected_groups);
                                },
                                function (Builder $builder) use ($selection, $newVersionResolved) {
                                    if ($newVersionResolved) {
                                        return $builder->whereIn('replicated_rows_group_id', $selection->selected_groups);
                                    }

                                    return $builder->whereKey($selection->selected_groups);
                                },
                            )
                            ->update(['is_selected' => true]);

                        $model->save();

                        return;
                    }

                    $model->use_groups = false;

                    $model->mappedRows()->update(['is_selected' => false]);

                    $model->mappedRows()->getQuery()
                        ->when(
                            $selection->reject,

                            function (Builder $builder) use ($selection, $newVersionResolved) {
                                if ($newVersionResolved) {
                                    return $builder->whereNotIn('replicated_mapped_row_id', $selection->selected_rows);
                                }

                                return $builder->whereKeyNot($selection->selected_rows);
                            },
                            function (Builder $builder) use ($selection, $newVersionResolved) {
                                if ($newVersionResolved) {
                                    return $builder->whereIn('replicated_mapped_row_id', $selection->selected_rows);
                                }

                                return $builder->whereKey($selection->selected_rows);
                            },
                        )
                        ->update(['is_selected' => true]);

                    $model->sort_rows_column = $selection->sort_rows_column;
                    $model->sort_rows_direction = $selection->sort_rows_direction;

                    $model->save();
                });

                $this->connection->commit();
            } catch (Throwable $e) {
                $this->connection->rollBack();

                throw $e;
            } finally {
                $lock->release();
            }
        }
    }

    public function createRowsGroup(WorldwideQuoteVersion $quote, WorldwideDistribution $distribution, RowsGroupData $data): DistributionRowsGroup
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        /** @var WorldwideDistribution $distribution */
        $distribution = value(function () use ($distribution, $quote, $newVersionResolved): WorldwideDistribution {
            if ($newVersionResolved) {
                /** @var WorldwideDistribution $model */
                $model = $quote->worldwideDistributions()->where('replicated_distributor_quote_id', $distribution->getKey())->sole();

                return $model;
            }

            return $distribution;
        });

        $data->rows = value(function () use ($data, $distribution, $newVersionResolved): array {

            if ($newVersionResolved) {

                return $distribution->mappedRows()
                        ->whereIn('replicated_mapped_row_id', $data->rows)
                        ->pluck($distribution->mappedRows()->getRelated()->getQualifiedKeyName())
                        ->all();

            }

            return $data->rows;

        });

        $lock = $this->lockProvider->lock(
            Lock::UPDATE_WWDISTRIBUTION($distribution->getKey()),
            10
        );

        $lock->block(30);

        $this->connection->beginTransaction();

        try {
            /** @var DistributionRowsGroup $rowsGroup */
            $rowsGroup = tap(new DistributionRowsGroup([
                'worldwide_distribution_id' => $distribution->getKey(),
                'group_name' => $data->group_name,
                'search_text' => $data->search_text,
            ]))->save();

            $rowsGroup->rows()->sync($data->rows);

            $this->connection->commit();

            return $rowsGroup;
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    public function updateRowsGroup(WorldwideQuoteVersion $quote, WorldwideDistribution $distribution, DistributionRowsGroup $rowsGroup, RowsGroupData $data): DistributionRowsGroup
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        $distribution = value(function () use ($distribution, $quote, $newVersionResolved): WorldwideDistribution {
            if ($newVersionResolved) {
                /** @var WorldwideDistribution $model */
                $model = $quote->worldwideDistributions()->where('replicated_distributor_quote_id', $distribution->getKey())->sole();

                return $model;
            }

            return $distribution;
        });

        $rowsGroup = value(function () use ($rowsGroup, $distribution, $newVersionResolved): DistributionRowsGroup {
            if ($newVersionResolved) {
                /** @var DistributionRowsGroup $model */
                $model = $distribution->rowsGroups()->where('replicated_rows_group_id', $rowsGroup->getKey())->sole();

                return $model;
            }

            return $rowsGroup;
        });

        $data->rows = value(function () use ($data, $distribution, $newVersionResolved): array {

            if ($newVersionResolved) {

                return $distribution->mappedRows()
                    ->whereIn('replicated_mapped_row_id', $data->rows)
                    ->pluck($distribution->mappedRows()->getRelated()->getQualifiedKeyName())
                    ->all();

            }

            return $data->rows;

        });

        $lock = $this->lockProvider->lock(
            Lock::UPDATE_WWDISTRIBUTION($distribution->getKey()),
            10
        );

        $lock->block(30);

        $this->connection->beginTransaction();

        try {
            $rowsGroup->forceFill([
                'group_name' => $data->group_name,
                'search_text' => $data->search_text,
            ])->save();

            $rowsGroup->rows()->sync($data->rows);

            $this->connection->commit();

            return $rowsGroup;
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    public function deleteRowsGroup(WorldwideQuoteVersion $quote, WorldwideDistribution $distribution, DistributionRowsGroup $rowsGroup): void
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        $distribution = value(function () use ($distribution, $quote, $newVersionResolved): WorldwideDistribution {
            if ($newVersionResolved) {
                /** @var WorldwideDistribution $model */
                $model = $quote->worldwideDistributions()->where('replicated_distributor_quote_id', $distribution->getKey())->sole();

                return $model;
            }

            return $distribution;
        });

        $rowsGroup = value(function () use ($rowsGroup, $distribution, $newVersionResolved): DistributionRowsGroup {
            if ($newVersionResolved) {
                /** @var DistributionRowsGroup $model */
                $model = $distribution->rowsGroups()->where('replicated_rows_group_id', $rowsGroup->getKey())->sole();

                return $model;
            }

            return $rowsGroup;
        });

        $lock = $this->lockProvider->lock(
            Lock::UPDATE_WWDISTRIBUTION($distribution->getKey()),
            10
        );

        $lock->block(30);

        $this->connection->beginTransaction();

        try {
            $rowsGroup->delete();

            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    public function moveRowsBetweenGroups(WorldwideQuoteVersion $quote, WorldwideDistribution $worldwideDistribution, DistributionRowsGroup $outputRowsGroup, DistributionRowsGroup $inputRowsGroup, array $rows): void
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        $worldwideDistribution = value(function () use ($worldwideDistribution, $quote, $newVersionResolved): WorldwideDistribution {
            if ($newVersionResolved) {
                /** @var WorldwideDistribution $model */
                $model = $quote->worldwideDistributions()->where('replicated_distributor_quote_id', $worldwideDistribution->getKey())->sole();

                return $model;
            }

            return $worldwideDistribution;
        });

        $outputRowsGroup = value(function () use ($outputRowsGroup, $worldwideDistribution, $newVersionResolved): DistributionRowsGroup {
            if ($newVersionResolved) {
                /** @var DistributionRowsGroup $model */
                $model = $worldwideDistribution->rowsGroups()->where('replicated_rows_group_id', $outputRowsGroup->getKey())->sole();

                return $model;
            }

            return $outputRowsGroup;
        });

        $inputRowsGroup = value(function () use ($inputRowsGroup, $worldwideDistribution, $newVersionResolved): DistributionRowsGroup {
            if ($newVersionResolved) {
                /** @var DistributionRowsGroup $model */
                $model = $worldwideDistribution->rowsGroups()->where('replicated_rows_group_id', $inputRowsGroup->getKey())->sole();

                return $model;
            }

            return $inputRowsGroup;
        });

        $rows = value(function () use ($outputRowsGroup, $newVersionResolved, $rows) {
            if ($newVersionResolved) {
                return $outputRowsGroup->rows()->whereIn('replicated_mapped_row_id', $rows)->pluck('id')->all();
            }

            return $rows;
        });

        $lock = $this->lockProvider->lock(
            Lock::UPDATE_WWDISTRIBUTION($worldwideDistribution->getKey()),
            10
        );

        $lock->block(30);

        $this->connection->beginTransaction();

        try {
            $outputRowsGroup->rows()->detach($rows);
            $inputRowsGroup->rows()->syncWithoutDetaching($rows);

            $this->connection->commit();
        } catch (Throwable $e) {
            $this->connection->rollBack();

            throw $e;
        } finally {
            $lock->release();
        }
    }

    public function setDistributionsMargin(WorldwideQuoteVersion $quote, DistributionMarginTaxCollection $collection): void
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        foreach ($collection as $marginTaxData) {
            $violations = $this->validator->validate($marginTaxData);

            if (count($violations)) {
                throw new ValidationFailedException($marginTaxData, $violations);
            }
        }

        $collection->rewind();

        $distributorQuoteKeys = Arr::pluck($collection, 'worldwide_distribution_id');

        /** @var Collection<WorldwideDistribution> */
        $distributions = WorldwideDistribution::query()
            ->when($newVersionResolved, function (Builder $builder) use ($distributorQuoteKeys) {
                $builder->whereIn('replicated_distributor_quote_id', $distributorQuoteKeys);
            }, function (Builder $builder) use ($distributorQuoteKeys) {
                $builder->whereKey($distributorQuoteKeys);
            })
            ->get(['id', 'replicated_distributor_quote_id', 'margin_value', 'created_at', 'updated_at']);

        $distributions = value(function () use ($distributions, $newVersionResolved): Collection {
            if ($newVersionResolved) {
                return $distributions->keyBy('replicated_distributor_quote_id');
            }

            return $distributions->keyBy('id');
        });

        foreach ($collection as $distribution) {
            /** @var WorldwideDistribution $model */
            $model = $distributions->get($distribution->worldwide_distribution_id);

            $model->margin_value = $distribution->margin_value;
            $model->tax_value = $distribution->tax_value;

            if (is_null($model->margin_value)) {
                $model->margin_method = null;
                $model->quote_type = null;
            } else {
                $model->margin_method = $distribution->margin_method;
                $model->quote_type = $distribution->quote_type;
            }

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWDISTRIBUTION($model->getKey()),
                10
            );

            $lock->block(30, function () use ($model) {

                $this->connection->transaction(fn() => $model->save());

            });
        }
    }

    public function storeDistributorFile(WorldwideQuoteVersion $quote, WorldwideDistribution $worldwideDistribution, UploadedFile $file): QuoteFile
    {
        $worldwideDistribution = value(function () use ($quote, $worldwideDistribution): WorldwideDistribution {
            if ($quote->wasRecentlyCreated) {
                /** @var WorldwideDistribution $model */
                $model = $quote->worldwideDistributions()->where('replicated_distributor_quote_id', $worldwideDistribution->getKey())->firstOrFail();

                return $model;
            }

            return $worldwideDistribution;
        });

        $fileName = $this->storage->put($worldwideDistribution->getKey(), $file);

        $fileService = (new QuoteFileService);

        $pagesCount = $fileService->countPages($this->storage->path($fileName));

        $quoteFileFormat = $fileService->determineFileFormat($file->getClientOriginalName());

        $filePath = Str::after($this->storage->path($fileName), Storage::path(''));

        $quoteFile = tap(new QuoteFile(), function (QuoteFile $quoteFile) use ($pagesCount, $quoteFileFormat, $file, $filePath) {
            $quoteFile->original_file_path = $filePath;
            $quoteFile->original_file_name = $file->getClientOriginalName();
            $quoteFile->quote_file_format_id = optional($quoteFileFormat)->getKey();
            $quoteFile->pages = $pagesCount;
            $quoteFile->file_type = QFT_WWPL;

            $this->connection->transaction(fn() => $quoteFile->save());
        });

        $worldwideDistribution->distributorFile()->associate($quoteFile);

        // Unset a cached exchange rate of mapped rows
        // when a new file attached to the quote.
        $worldwideDistribution->distribution_exchange_rate = null;

        // Unset imported_at timestamp
        // when a new file attached to the quote.
        $worldwideDistribution->imported_at = null;

        $lock = $this->lockProvider->lock(
            Lock::UPDATE_WWDISTRIBUTION($worldwideDistribution->getKey()),
            10
        );

        $lock->block(30, function () use ($worldwideDistribution) {

            $this->connection->transaction(function () use ($worldwideDistribution) {
                /** @var WorldwideDistribution $worldwideDistribution */

                $worldwideDistribution->save();

                $worldwideDistribution->rowsGroups()->delete();

                // It's required to detach the existing mapped columns
                // when a new file attached to the distributor quote.
                $worldwideDistribution->templateFields()->update(['importable_column_id' => null]);

            });

        });

        return $quoteFile;
    }

    public function storeScheduleFile(WorldwideQuoteVersion $quote, WorldwideDistribution $worldwideDistribution, UploadedFile $file): QuoteFile
    {
        $worldwideDistribution = value(function () use ($quote, $worldwideDistribution): WorldwideDistribution {
            if ($quote->wasRecentlyCreated) {
                /** @var WorldwideDistribution $model */
                $model = $quote->worldwideDistributions()->where('replicated_distributor_quote_id', $worldwideDistribution->getKey())->firstOrFail();

                return $model;
            }

            return $worldwideDistribution;
        });

        $fileName = $this->storage->put($worldwideDistribution->getKey(), $file);

        $fileService = (new QuoteFileService);

        $pagesCount = $fileService->countPages($this->storage->path($fileName));

        $quoteFileFormat = $fileService->determineFileFormat($file->getClientOriginalName());

        $filePath = Str::after($this->storage->path($fileName), Storage::path(''));

        $quoteFile = tap(new QuoteFile(), function (QuoteFile $quoteFile) use ($pagesCount, $filePath, $file, $quoteFileFormat) {
            $quoteFile->original_file_path = $filePath;
            $quoteFile->original_file_name = $file->getClientOriginalName();
            $quoteFile->quote_file_format_id = optional($quoteFileFormat)->getKey();
            $quoteFile->pages = $pagesCount;
            $quoteFile->file_type = QFT_PS;

            $this->connection->transaction(fn() => $quoteFile->save());
        });

        $worldwideDistribution->scheduleFile()->associate($quoteFile);

        // Unset imported_at timestamp
        // when a new file attached to the quote.
        $worldwideDistribution->imported_at = null;

        $lock = $this->lockProvider->lock(
            Lock::UPDATE_WWDISTRIBUTION($worldwideDistribution->getKey()),
            10
        );

        $lock->block(30, function () use ($worldwideDistribution) {

            $this->connection->transaction(fn() => $worldwideDistribution->save());
        });

        return $quoteFile;
    }

    public function deleteDistribution(WorldwideQuoteVersion $quote, WorldwideDistribution $worldwideDistribution): bool
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        if ($newVersionResolved) {
            $worldwideDistribution = $quote->worldwideDistributions()->where('replicated_distributor_quote_id', $worldwideDistribution->getKey())->sole();
        }

        return $this->connection->transaction(fn() => $worldwideDistribution->delete());
    }

    /**
     * @inheritDoc
     */
    public function updateMappedRowOfDistribution(WorldwideQuoteVersion $quote, WorldwideDistribution $worldwideDistribution, MappedRow $mappedRow, UpdateMappedRowFieldCollection $rowData): MappedRow
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        foreach ($rowData as $fieldData) {
            $violations = $this->validator->validate($fieldData);

            if (count($violations)) {
                throw new ValidationFailedException($fieldData, $violations);
            }
        }

        $rowData->rewind();

        if ($newVersionResolved) {
            $worldwideDistribution = $quote->worldwideDistributions()->where('replicated_distributor_quote_id', $worldwideDistribution->getKey())->sole();
        }

        if ($newVersionResolved) {
            $mappedRow = $worldwideDistribution->mappedRows()->where('replicated_mapped_row_id', $mappedRow->getKey())->sole();
        }

        foreach ($rowData as $fieldData) {
            $mappedRow->{$fieldData->field_name} = $fieldData->field_value;
        }

        $this->connection->transaction(fn() => $mappedRow->save());

        return $mappedRow;
    }

    private function replicateAddressModelsOfPrimaryAccount(Collection $addressCollection): array
    {
        $newAddressModels = [];
        $newAddressPivots = [];

        foreach ($addressCollection as $address) {
            $newAddress = $address->replicate();
            $newAddress->{$newAddress->getKeyName()} = (string)Uuid::generate(4);
            $newAddress->{$newAddress->getCreatedAtColumn()} = $newAddress->freshTimestampString();
            $newAddress->{$newAddress->getUpdatedAtColumn()} = $newAddress->freshTimestampString();

            $newAddressModels[] = $newAddress;
            $newAddressPivots[$newAddress->getKey()] = ['replicated_address_id' => $address->getKey()];
        }

        return [$newAddressModels, $newAddressPivots];
    }

    private function replicateContactModelsOfPrimaryAccount(Collection $contactCollection): array
    {
        $newContactModels = [];
        $newContactPivots = [];

        foreach ($contactCollection as $contact) {
            $newContact = $contact->replicate();
            $newContact->{$newContact->getKeyName()} = (string)Uuid::generate(4);
            $newContact->{$newContact->getCreatedAtColumn()} = $newContact->freshTimestampString();
            $newContact->{$newContact->getUpdatedAtColumn()} = $newContact->freshTimestampString();

            $newContactModels[] = $newContact;
            $newContactPivots[$newContact->getKey()] = ['replicated_contact_id' => $contact->getKey()];
        }

        return [$newContactModels, $newContactPivots];
    }
}
