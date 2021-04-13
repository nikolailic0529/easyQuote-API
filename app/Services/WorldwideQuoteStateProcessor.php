<?php

namespace App\Services;

use App\Contracts\{Services\ManagesExchangeRates,
    Services\ProcessesWorldwideDistributionState,
    Services\ProcessesWorldwideQuoteState};
use App\DTO\QuoteStages\{AddressesContactsStage,
    ContractDetailsStage,
    ContractDiscountStage,
    ContractMarginTaxStage,
    DraftStage,
    ImportStage,
    InitStage,
    MappingStage,
    PackAssetsCreationStage,
    PackAssetsReviewStage,
    PackDetailsStage,
    PackDiscountStage,
    PackMarginTaxStage,
    ReviewStage,
    SubmitStage};
use App\DTO\WorldwideQuote\DistributionAddressData;
use App\DTO\WorldwideQuote\DistributionContactData;
use App\DTO\WorldwideQuote\DistributionImportData;
use App\DTO\WorldwideQuote\MarkWorldwideQuoteAsDeadData;
use App\DTO\WorldwideQuote\OpportunityAddressData;
use App\DTO\WorldwideQuote\OpportunityContactData;
use App\Enum\{ContractQuoteStage, Lock, QuoteStatus};
use App\Events\WorldwideQuote\WorldwideContractQuoteDetailsStepProcessed;
use App\Events\WorldwideQuote\WorldwideContractQuoteDiscountStepProcessed;
use App\Events\WorldwideQuote\WorldwideContractQuoteImportStepProcessed;
use App\Events\WorldwideQuote\WorldwideContractQuoteMappingReviewStepProcessed;
use App\Events\WorldwideQuote\WorldwideContractQuoteMappingStepProcessed;
use App\Events\WorldwideQuote\WorldwideContractQuoteMarginStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteAssetsCreationStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteAssetsReviewStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteContactsStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteDetailsStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteDiscountStepProcessed;
use App\Events\WorldwideQuote\WorldwidePackQuoteMarginStepProcessed;
use App\Events\WorldwideQuote\WorldwideQuoteActivated;
use App\Events\WorldwideQuote\WorldwideQuoteDeactivated;
use App\Events\WorldwideQuote\WorldwideQuoteDeleted;
use App\Events\WorldwideQuote\WorldwideQuoteDrafted;
use App\Events\WorldwideQuote\WorldwideQuoteInitialized;
use App\Events\WorldwideQuote\WorldwideQuoteMarkedAsAlive;
use App\Events\WorldwideQuote\WorldwideQuoteMarkedAsDead;
use App\Events\WorldwideQuote\WorldwideQuoteSubmitted;
use App\Events\WorldwideQuote\WorldwideQuoteUnraveled;
use App\Jobs\IndexSearchableEntity;
use App\Models\{Address,
    Contact,
    Data\Currency,
    Opportunity,
    Quote\DistributionFieldColumn,
    Quote\WorldwideDistribution,
    Quote\WorldwideQuote,
    Quote\WorldwideQuoteVersion,
    QuoteFile\DistributionRowsGroup,
    QuoteFile\ImportedRow,
    QuoteFile\MappedRow,
    QuoteFile\QuoteFile,
    QuoteFile\ScheduleData,
    User,
    WorldwideQuoteAsset};
use App\Services\Exceptions\ValidationException;
use App\Services\WorldwideQuote\Models\ReplicatedVersionData;
use App\Services\WorldwideQuote\WorldwideQuoteReplicator;
use Illuminate\Contracts\{Bus\Dispatcher as BusDispatcher, Cache\LockProvider, Events\Dispatcher as EventDispatcher};
use Illuminate\Database\{ConnectionInterface, Eloquent\Builder, Eloquent\Collection, Eloquent\Model};
use Illuminate\Support\{Carbon, Facades\DB};
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;

class WorldwideQuoteStateProcessor implements ProcessesWorldwideQuoteState
{
    const QUOTE_NUM_FMT = "EPD-WW-DP-%'.07d";

    protected ConnectionInterface $connection;

    protected LockProvider $lockProvider;

    protected ValidatorInterface $validator;

    protected BusDispatcher $busDispatcher;

    protected ManagesExchangeRates $exchangeRateService;

    protected EventDispatcher $eventDispatcher;
    /**
     * @var ProcessesWorldwideDistributionState
     */
    private ProcessesWorldwideDistributionState $distributionProcessor;

    /**
     * WorldwideQuoteStateProcessor constructor.
     * @param ConnectionInterface $connection
     * @param LockProvider $lockProvider
     * @param ValidatorInterface $validator
     * @param ManagesExchangeRates $exchangeRateService
     * @param BusDispatcher $busDispatcher
     * @param EventDispatcher $eventDispatcher
     * @param ProcessesWorldwideDistributionState $distributionProcessor
     */
    public function __construct(ConnectionInterface $connection,
                                LockProvider $lockProvider,
                                ValidatorInterface $validator,
                                ManagesExchangeRates $exchangeRateService,
                                BusDispatcher $busDispatcher,
                                EventDispatcher $eventDispatcher,
                                ProcessesWorldwideDistributionState $distributionProcessor)
    {
        $this->connection = $connection;
        $this->lockProvider = $lockProvider;
        $this->validator = $validator;
        $this->exchangeRateService = $exchangeRateService;
        $this->busDispatcher = $busDispatcher;
        $this->eventDispatcher = $eventDispatcher;
        $this->distributionProcessor = $distributionProcessor;
    }

    public function initializeQuote(InitStage $stage): WorldwideQuote
    {
        $violations = $this->validator->validate($stage);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap(new WorldwideQuote, function (WorldwideQuote $quote) use ($stage) {
            $quoteLock = $this->lockProvider->lock(Lock::CREATE_WWQUOTE, 10);

            /** @var Opportunity $opportunity */
            $opportunity = Opportunity::query()->findOrFail($stage->opportunity_id);

            $opportunitySuppliers = $opportunity->opportunitySuppliers;

            // The lock is required for issuing a new quote number.
            $quoteLock->block(30, function () use ($stage, $quote, $opportunity, $opportunitySuppliers) {
                $quote->{$quote->getKeyName()} = (string)Uuid::generate(4);
                $quote->contractType()->associate($stage->contract_type_id);
                $quote->opportunity()->associate($stage->opportunity_id);
                $quote->user()->associate($stage->user_id);
                $this->assignNewQuoteNumber($quote);

                $activeVersion = tap(new WorldwideQuoteVersion(), function (WorldwideQuoteVersion $version) use ($opportunity, $quote, $stage) {
                    $version->{$version->getKeyName()} = (string)Uuid::generate(4);
                    $version->user()->associate($stage->user_id);
                    $version->completeness = $stage->stage;
                    $version->quote_expiry_date = $stage->quote_expiry_date->toDateString();
                    $version->user_version_sequence_number = 1;

                    if ($quote->contract_type_id === CT_PACK) {
                        $version->buy_price = $opportunity->purchase_price;

                        if (!is_null($opportunity->purchase_price_currency_code)) {
                            $version->quoteCurrency()->associate(
                                Currency::query()->where('code', $opportunity->purchase_price_currency_code)->first()
                            );
                        }
                    }
                });

                $quote->activeVersion()->associate($activeVersion);

                $this->connection->transaction(function () use ($activeVersion, $quote, $opportunitySuppliers) {
                    $activeVersion->save();

                    $quote->save();

                    $activeVersion->worldwideQuote()->associate($quote)->save();

                    if ($quote->contract_type_id !== CT_CONTRACT) {
                        return;
                    }

                    // Populating the Distributor Quotes from Opportunity Suppliers.
                    foreach ($opportunitySuppliers as $supplier) {
                        $this->distributionProcessor->initializeDistribution($activeVersion, $supplier->getKey());
                    }

                });
            });

            $opportunityLock = $this->lockProvider->lock(Lock::UPDATE_OPPORTUNITY($stage->opportunity_id), 10);

            $opportunityLock->block(30, function () use ($stage, $quote) {

                $opportunity = $quote->opportunity;
                $primaryAccount = $opportunity->primaryAccount;

                if (is_null($primaryAccount)) {
                    return;
                }

                // When a primary account is present on the opportunity,
                // we will copy its' all addresses & contacts and attach to the opportunity.

                $this->connection->transaction(function () use ($opportunity, $primaryAccount) {
                    $opportunity->addresses()->delete();
                    $opportunity->contacts()->delete();

                    $addresses = $primaryAccount->addresses->mapWithKeys(function (Address $address) use ($opportunity) {
                        $replicatedAddress = tap($address->replicate(), function (Address $address) {
                            $address->save();
                        });

                        return [$replicatedAddress->getKey() => ['is_default' => $address->pivot->is_default]];
                    });

                    $opportunity->addresses()->syncWithoutDetaching($addresses->all());

                    $contacts = $primaryAccount->contacts->mapWithKeys(function (Contact $contact) use ($opportunity) {
                        $replicatedContact = tap($contact->replicate(), function (Contact $contact) {
                            $contact->save();
                        });

                        return [$replicatedContact->getKey() => ['is_default' => $contact->pivot->is_default]];
                    });

                    $opportunity->contacts()->syncWithoutDetaching($contacts->all());
                });
            });

            $this->busDispatcher->dispatch(
                new IndexSearchableEntity($quote)
            );

            $this->eventDispatcher->dispatch(
                new WorldwideQuoteInitialized($quote)
            );
        });
    }

    protected function assignNewQuoteNumber(WorldwideQuote $quote): void
    {
        $highestNumber = $this->connection->table('worldwide_quotes')->max('sequence_number');
        $newNumber = $highestNumber + 1;

        $quote->quote_number = sprintf(static::QUOTE_NUM_FMT, $newNumber);
        $quote->sequence_number = $newNumber;
    }

    public function switchActiveVersionOfQuote(WorldwideQuote $quote, WorldwideQuoteVersion $version): void
    {
        $lock = $this->lockProvider->lock(
            Lock::UPDATE_WWQUOTE($quote->getKey()),
            10
        );

        $quote->activeVersion()->associate($version);

        $lock->block(30, function () use ($quote) {

            $this->connection->transaction(function () use ($quote) {

                $quote->save();

            });

        });
    }

    public function deleteVersionOfQuote(WorldwideQuote $quote, WorldwideQuoteVersion $version): void
    {
        $lock = $this->lockProvider->lock(
            Lock::UPDATE_WWQUOTE($quote->getKey()),
            10
        );

        $lock->block(30, function () use ($version) {

            $this->connection->transaction(function () use ($version) {

                $version->delete();

            });

        });
    }

    public function processQuoteAddressesContactsStep(WorldwideQuoteVersion $quote, AddressesContactsStage $stage): WorldwideQuoteVersion
    {
        if ($quote->worldwideQuote->contract_type_id !== CT_PACK) {
            throw new ValidationException('A processing of this stage is intended for Pack Quotes only.');
        }

        $violations = $this->validator->validate($stage);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        foreach ($stage->addresses as $addressData) {
            $violations = $this->validator->validate($addressData);

            if (count($violations)) {
                throw new ValidationException($violations);
            }
        }

        foreach ($stage->contacts as $contactData) {
            $violations = $this->validator->validate($contactData);

            if (count($violations)) {
                throw new ValidationException($violations);
            }
        }

        return tap($quote, function (WorldwideQuoteVersion $quote) use ($stage) {
            $oldQuote = (new WorldwideQuote())->setRawAttributes($quote->worldwideQuote->getRawOriginal());

            $quoteLock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $quote->company()->associate($stage->company_id);
            $quote->quoteCurrency()->associate($stage->quote_currency_id);
            $quote->quoteTemplate()->associate($stage->quote_template_id);
            $quote->buy_price = $stage->buy_price;
            $quote->quote_expiry_date = $stage->quote_expiry_date->toDateString();
            $quote->payment_terms = $stage->payment_terms;
            $quote->completeness = $stage->stage;

            $quoteLock->block(30, function () use ($quote) {
                $this->connection->transaction(fn() => $quote->save());
            });

            $opportunityLock = $this->lockProvider->lock(
                Lock::UPDATE_OPPORTUNITY($quote->worldwideQuote->opportunity_id),
                10
            );

            $opportunityLock->block(30, function () use ($quote, $stage) {
                $opportunity = $quote->worldwideQuote->opportunity;

                $existingAddressModelKeys = array_map(function (OpportunityAddressData $addressData) {
                    return $addressData->address_id;
                }, iterator_to_array($stage->addresses));

                $existingAddressModelKeys = array_values(array_filter($existingAddressModelKeys));

                $existingContactModelKeys = array_map(function (OpportunityContactData $contactData) {
                    return $contactData->contact_id;
                }, iterator_to_array($stage->contacts));

                $existingContactModelKeys = array_values(array_filter($existingContactModelKeys));

                $this->connection->transaction(function () use ($opportunity, $stage, $existingAddressModelKeys, $existingContactModelKeys) {
                    $opportunity->addresses()->whereKeyNot($existingAddressModelKeys)->delete();
                    $opportunity->contacts()->whereKeyNot($existingContactModelKeys)->delete();

                    $stage->addresses->rewind();
                    $stage->contacts->rewind();

                    $addressModelKeys = [];
                    $contactModelKeys = [];

                    foreach ($stage->addresses as $addressData) {
                        if (!is_null($addressData->address_id)) {
                            Address::query()->whereKey($addressData->address_id)
                                ->update([
                                    'address_type' => $addressData->address_type,
                                    'address_1' => $addressData->address_1,
                                    'address_2' => $addressData->address_2,
                                    'city' => $addressData->city,
                                    'state' => $addressData->state,
                                    'post_code' => $addressData->post_code,
                                    'country_id' => $addressData->country_id,
                                ]);

                            $addressModelKeys[$addressData->address_id] = ['is_default' => $addressData->is_default];
                        } else {
                            with(new Address(), function (Address $address) use ($addressData, &$addressModelKeys) {
                                $address->address_type = $addressData->address_type;
                                $address->address_1 = $addressData->address_1;
                                $address->address_2 = $addressData->address_2;
                                $address->city = $addressData->city;
                                $address->state = $addressData->state;
                                $address->post_code = $addressData->post_code;
                                $address->country_id = $addressData->country_id;

                                $address->save();

                                $addressModelKeys[$address->getKey()] = ['is_default' => $addressData->is_default];
                            });
                        }
                    }

                    foreach ($stage->contacts as $contactData) {
                        if (!is_null($contactData->contact_id)) {
                            Contact::query()->whereKey($contactData->contact_id)
                                ->update([
                                    'contact_type' => $contactData->contact_type,
                                    'first_name' => $contactData->first_name,
                                    'last_name' => $contactData->last_name,
                                    'email' => $contactData->email,
                                    'mobile' => $contactData->mobile,
                                    'phone' => $contactData->phone,
                                    'job_title' => $contactData->job_title,
                                    'is_verified' => $contactData->is_verified,
                                ]);

                            $contactModelKeys[$contactData->contact_id] = ['is_default' => $contactData->is_default];
                        } else {
                            with(new Contact(), function (Contact $contact) use ($contactData, &$contactModelKeys) {
                                $contact->contact_type = $contactData->contact_type;
                                $contact->first_name = $contactData->first_name;
                                $contact->last_name = $contactData->last_name;
                                $contact->email = $contactData->email;
                                $contact->mobile = $contactData->mobile;
                                $contact->phone = $contactData->phone;
                                $contact->job_title = $contactData->job_title;
                                $contact->is_verified = $contactData->is_verified;

                                $contact->save();

                                $contactModelKeys[$contact->getKey()] = ['is_default' => $contactData->is_default];
                            });
                        }
                    }

                    $opportunity->addresses()->sync($addressModelKeys);
                    $opportunity->contacts()->sync($contactModelKeys);
                });
            });

            $this->eventDispatcher->dispatch(
                new WorldwidePackQuoteContactsStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote
                )
            );
        });
    }

    public function processPackQuoteMarginStep(WorldwideQuoteVersion $quote, PackMarginTaxStage $stage): WorldwideQuoteVersion
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        if ($quote->worldwideQuote->contract_type_id !== CT_PACK) {
            throw new ValidationException('A processing of this stage is intended for Pack Quotes only.');
        }

        $violations = $this->validator->validate($stage);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap($quote, function (WorldwideQuoteVersion $quote) use ($stage) {
            $oldQuote = (new WorldwideQuote())->setRawAttributes($quote->worldwideQuote->getRawOriginal());

            $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

            $quote->quote_type = $stage->quote_type;
            $quote->margin_method = $stage->margin_method;
            $quote->margin_value = $stage->margin_value;
            $quote->tax_value = $stage->tax_value;
            $quote->completeness = $stage->stage;

            $lock->block(30, function () use ($stage, $quote) {
                $this->connection->transaction(fn() => $quote->save());

            });

            $this->eventDispatcher->dispatch(
                new WorldwidePackQuoteMarginStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote
                )
            );

        });
    }

    public function processPackQuoteDiscountStep(WorldwideQuoteVersion $quote, PackDiscountStage $stage): WorldwideQuoteVersion
    {
        if ($quote->worldwideQuote->contract_type_id !== CT_PACK) {
            throw new ValidationException('A processing of this stage is intended for Pack Quotes only.');
        }

        $violations = $this->validator->validate($stage);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap($quote, function (WorldwideQuoteVersion $quote) use ($stage) {
            $oldQuote = (new WorldwideQuote())->setRawAttributes($quote->worldwideQuote->getRawOriginal());

            if (!is_null($stage->predefinedDiscounts)) {
                $quote->multiYearDiscount()->associate($stage->predefinedDiscounts->multiYearDiscount);
                $quote->prePayDiscount()->associate($stage->predefinedDiscounts->prePayDiscount);
                $quote->promotionalDiscount()->associate($stage->predefinedDiscounts->promotionalDiscount);
                $quote->snDiscount()->associate($stage->predefinedDiscounts->snDiscount);
            } else {
                $quote->multiYearDiscount()->dissociate();
                $quote->prePayDiscount()->dissociate();
                $quote->promotionalDiscount()->dissociate();
                $quote->snDiscount()->dissociate();
            }

            $quote->custom_discount = $stage->customDiscount;
            $quote->completeness = $stage->stage;

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $lock->block(30, function () use ($quote) {
                $this->connection->transaction(fn() => $quote->save());
            });

            $this->eventDispatcher->dispatch(
                new WorldwidePackQuoteDiscountStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote
                )
            );
        });
    }

    public function processPackQuoteDetailsStep(WorldwideQuoteVersion $quote, PackDetailsStage $stage): WorldwideQuoteVersion
    {
        if ($quote->worldwideQuote->contract_type_id !== CT_PACK) {
            throw new ValidationException('A processing of this stage is intended for Pack Quotes only.');
        }

        $violations = $this->validator->validate($stage);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap($quote, function (WorldwideQuoteVersion $quote) use ($stage) {
            $oldQuote = (new WorldwideQuote())->setRawAttributes($quote->worldwideQuote->getRawOriginal());

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $quote->completeness = $stage->stage;
            $quote->pricing_document = $stage->pricing_document;
            $quote->service_agreement_id = $stage->service_agreement_id;
            $quote->system_handle = $stage->system_handle;
            $quote->additional_details = $stage->additional_details;

            $lock->block(30, function () use ($quote) {
                $this->connection->transaction(fn() => $quote->save());
            });

            $this->eventDispatcher->dispatch(
                new WorldwidePackQuoteDetailsStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote
                )
            );
        });
    }

    public function processQuoteAssetsCreationStep(WorldwideQuoteVersion $quote, PackAssetsCreationStage $stage): WorldwideQuoteVersion
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        $violations = $this->validator->validate($stage);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap($quote, function (WorldwideQuoteVersion $quote) use ($stage) {
            $oldQuote = (new WorldwideQuote())->setRawAttributes($quote->worldwideQuote->getRawOriginal());

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $quote->completeness = $stage->stage;

            $lock->block(30, function () use ($quote) {
                $this->connection->transaction(
                    fn() => $quote->save()
                );
            });

            $this->eventDispatcher->dispatch(
                new WorldwidePackQuoteAssetsCreationStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote
                )
            );

        });
    }

    public function processQuoteAssetsReviewStep(WorldwideQuoteVersion $quote, PackAssetsReviewStage $stage): WorldwideQuoteVersion
    {
        $violations = $this->validator->validate($stage);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return tap($quote, function (WorldwideQuoteVersion $quote) use ($stage) {

            $newVersionResolved = $quote->wasRecentlyCreated;

            $oldQuote = (new WorldwideQuote())->setRawAttributes($quote->worldwideQuote->getRawOriginal());

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $quote->completeness = $stage->stage;
            $quote->sort_rows_column = $stage->sort_rows_column;
            $quote->sort_rows_direction = $stage->sort_rows_direction;

            $lock->block(30, function () use ($quote, $stage, $newVersionResolved) {

                $this->connection->transaction(function () use ($quote, $stage, $newVersionResolved) {
                    $quote->save();

                    $quote->assets()->update(['is_selected' => false]);

                    $quote->assets()
                        ->when($stage->reject, function (Builder $builder) use ($stage, $newVersionResolved) {
                            if ($newVersionResolved) {
                                return $builder->whereNotIn('replicated_asset_id', $stage->selected_rows);
                            }

                            return $builder->whereKeyNot($stage->selected_rows);
                        }, function (Builder $builder) use ($newVersionResolved, $stage) {
                            if ($newVersionResolved) {
                                return $builder->whereIn('replicated_asset_id', $stage->selected_rows);
                            }


                            return $builder->whereKey($stage->selected_rows);
                        })
                        ->update(['is_selected' => true]);
                });

            });

            $this->eventDispatcher->dispatch(
                new WorldwidePackQuoteAssetsReviewStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote
                )
            );

        });
    }

    public function processQuoteImportStep(WorldwideQuoteVersion $quoteVersion, ImportStage $stage): WorldwideQuoteVersion
    {
        $newVersionResolved = $quoteVersion->wasRecentlyCreated;

        if ($quoteVersion->worldwideQuote->contract_type_id !== CT_CONTRACT) {
            throw new ValidationException('A processing of this stage is intended for Contract Quotes only.');
        }

        $violations = $this->validator->validate($stage);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        foreach ($stage->distributions_data as $distributionData) {
            $violations = $this->validator->validate($distributionData);

            if (count($violations)) {
                throw new ValidationException($violations);
            }
        }

        /** @var DistributionImportData[] $distributionsDataDictionary */
        $distributionsDataDictionary = [];

        foreach ($stage->distributions_data as $distributionData) {
            $distributionsDataDictionary[$distributionData->distribution_id] = $distributionData;
        }

        $oldQuote = with($quoteVersion, function (WorldwideQuoteVersion $quoteVersion) {
            $oldQuote = (new WorldwideQuote())->setRawAttributes($quoteVersion->worldwideQuote->getRawOriginal());

            $oldQuote->setRelation('activeVersion', (new WorldwideQuoteVersion())->setRawAttributes($quoteVersion->getRawOriginal()));

            return $oldQuote;
        });

        $lock = $this->lockProvider->lock(
            Lock::UPDATE_WWQUOTE($quoteVersion->getKey()),
            10
        );

        $quoteVersion->company()->associate($stage->company_id);
        $quoteVersion->quoteCurrency()->associate($stage->quote_currency_id);
        $quoteVersion->outputCurrency()->associate($stage->output_currency_id);
        $quoteVersion->quoteTemplate()->associate($stage->quote_template_id);

        $quoteVersion->completeness = $stage->stage;
        $quoteVersion->exchange_rate_margin = $stage->exchange_rate_margin;
        $quoteVersion->quote_expiry_date = $stage->quote_expiry_date->toDateString();
        $quoteVersion->payment_terms = $stage->payment_terms;

        $lock->block(30, fn() => $quoteVersion->saveOrFail());

        /** @var WorldwideDistribution[]|Collection $distributionModels */
        $distributionModels = $quoteVersion->worldwideDistributions()
            ->when($newVersionResolved, function (Builder $builder) use ($distributionsDataDictionary) {
                $builder->whereIn('replicated_distributor_quote_id', array_keys($distributionsDataDictionary));
            }, function (Builder $builder) use ($distributionsDataDictionary) {
                $builder->whereKey(array_keys($distributionsDataDictionary));
            })
            ->get();

        $distributionModels = value(function () use ($distributionModels, $newVersionResolved): array {
            if ($newVersionResolved) {
                return $distributionModels->keyBy('replicated_distributor_quote_id')->all();
            }

            return $distributionModels->getDictionary();
        });

        // Updating attributes of the quote distributions.
        foreach ($stage->distributions_data as $distributionData) {
            $model = $distributionModels[$distributionData->distribution_id];

            $lock = $this->lockProvider->lock(Lock::UPDATE_WWDISTRIBUTION($model->getKey()), 10);

            $model->country()->associate($distributionData->country_id);
            $model->distributionCurrency()->associate($distributionData->distribution_currency_id);
            $model->buy_price = $distributionData->buy_price;
            $model->calculate_list_price = $distributionData->calculate_list_price;

            $existingAddressModelKeys = array_map(function (DistributionAddressData $addressData) {
                return $addressData->address_id;
            }, $distributionData->addresses);

            $existingAddressModelKeys = array_values(array_filter($existingAddressModelKeys));

            $existingContactModelKeys = array_map(function (DistributionContactData $contactData) {
                return $contactData->contact_id;
            }, $distributionData->contacts);

            $existingContactModelKeys = array_values(array_filter($existingContactModelKeys));

            $lock->block(30, function () use ($existingAddressModelKeys, $existingContactModelKeys, $model, $distributionData) {

                $this->connection->transaction(function () use ($existingAddressModelKeys, $existingContactModelKeys, $model, $distributionData) {
                    $model->save();
                    $model->vendors()->sync($distributionData->vendors);

                    if (!empty($existingAddressModelKeys)) {
                        $model->addresses()->whereKeyNot($existingAddressModelKeys)->delete();
                    }

                    $addressModelKeys = [];

                    foreach ($distributionData->addresses as $addressData) {
                        if (!is_null($addressData->address_id)) {
                            Address::query()->whereKey($addressData->address_id)
                                ->update([
                                    'address_type' => $addressData->address_type,
                                    'address_1' => $addressData->address_1,
                                    'address_2' => $addressData->address_2,
                                    'city' => $addressData->city,
                                    'state' => $addressData->state,
                                    'post_code' => $addressData->post_code,
                                    'country_id' => $addressData->country_id,
                                ]);

                            $addressModelKeys[$addressData->address_id] = ['is_default' => $addressData->is_default];
                        } else {
                            with(new Address(), function (Address $address) use ($addressData, &$addressModelKeys) {
                                $address->address_type = $addressData->address_type;
                                $address->address_1 = $addressData->address_1;
                                $address->address_2 = $addressData->address_2;
                                $address->city = $addressData->city;
                                $address->state = $addressData->state;
                                $address->post_code = $addressData->post_code;
                                $address->country_id = $addressData->country_id;

                                $address->save();

                                $addressModelKeys[$address->getKey()] = ['is_default' => $addressData->is_default];
                            });
                        }
                    }

                    $model->addresses()->sync($addressModelKeys);

                    if (!empty($existingContactModelKeys)) {
                        $model->contacts()->whereKeyNot($existingContactModelKeys)->delete();
                    }

                    $contactModelKeys = [];

                    foreach ($distributionData->contacts as $contactData) {
                        if (!is_null($contactData->contact_id)) {
                            Contact::query()->whereKey($contactData->contact_id)
                                ->update([
                                    'contact_type' => $contactData->contact_type,
                                    'first_name' => $contactData->first_name,
                                    'last_name' => $contactData->last_name,
                                    'email' => $contactData->email,
                                    'mobile' => $contactData->mobile,
                                    'phone' => $contactData->phone,
                                    'job_title' => $contactData->job_title,
                                    'is_verified' => $contactData->is_verified,
                                ]);

                            $contactModelKeys[$contactData->contact_id] = ['is_default' => $contactData->is_default];
                        } else {
                            with(new Contact(), function (Contact $contact) use ($contactData, &$contactModelKeys) {
                                $contact->contact_type = $contactData->contact_type;
                                $contact->first_name = $contactData->first_name;
                                $contact->last_name = $contactData->last_name;
                                $contact->email = $contactData->email;
                                $contact->mobile = $contactData->mobile;
                                $contact->phone = $contactData->phone;
                                $contact->job_title = $contactData->job_title;
                                $contact->is_verified = $contactData->is_verified;

                                $contact->save();

                                $contactModelKeys[$contact->getKey()] = ['is_default' => $contactData->is_default];
                            });
                        }
                    }

                    $model->contacts()->sync($contactModelKeys);
                });

            });

        }

        // Updating computed price of mapped rows of the quote distributions.
        $distributions = $quoteVersion->worldwideDistributions()->with('distributionCurrency')->get(['id', 'worldwide_quote_id', 'distribution_currency_id', 'distribution_exchange_rate', 'created_at', 'updated_at']);

        $distributions->each(function (WorldwideDistribution $distribution) use ($quoteVersion, $distributionsDataDictionary) {
            $originalDistributionExchangeRate = $distribution->distribution_exchange_rate ?? 1;

            $distribution->distribution_exchange_rate = with($distribution, function (WorldwideDistribution $distribution) use ($quoteVersion) {
                if ($quoteVersion->quoteCurrency->is($distribution->distributionCurrency)) {
                    return 1.0;
                }

                $rateValue = $this->exchangeRateService->getTargetRate(
                    $distribution->distributionCurrency ?? new Currency(),
                    $quoteVersion->quoteCurrency
                );

                return $rateValue + ($rateValue * $quoteVersion->exchange_rate_margin / 100);
            });


            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWDISTRIBUTION($distribution->getKey()),
                10
            );

            $lock->block(30, function () use ($originalDistributionExchangeRate, $distribution) {

                $this->connection->transaction(function () use ($originalDistributionExchangeRate, $distribution) {

                    $distribution->mappedRows()->update(['price' => DB::raw("(price / $originalDistributionExchangeRate) * $distribution->distribution_exchange_rate")]);

                    $distribution->save();

                });

            });
        });

        return tap($quoteVersion, function (WorldwideQuoteVersion $quote) use ($oldQuote) {
            $this->busDispatcher->dispatch(
                new IndexSearchableEntity($quote->worldwideQuote)
            );

            $this->eventDispatcher->dispatch(
                new WorldwideContractQuoteImportStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote
                )
            );
        });
    }

    public function processQuoteMappingStep(WorldwideQuoteVersion $quote, MappingStage $stage): WorldwideQuoteVersion
    {
        $stage->mapping->rewind();

        return tap($quote, function (WorldwideQuoteVersion $quote) use ($stage) {
            $oldQuote = (new WorldwideQuote())->setRawAttributes($quote->worldwideQuote->getRawOriginal());

            $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->worldwide_quote_id), 10);

            $quote->completeness = $stage->stage;

            $lock->block(30, function () use ($quote) {
                $quote->save();
            });

            $this->eventDispatcher->dispatch(
                new WorldwideContractQuoteMappingStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote
                )
            );
        });
    }

    public function processQuoteMappingReviewStep(WorldwideQuoteVersion $quote, ReviewStage $stage): WorldwideQuoteVersion
    {
        return tap($quote, function (WorldwideQuoteVersion $quote) use ($stage) {

            $oldQuote = (new WorldwideQuote())->setRawAttributes($quote->worldwideQuote->getRawOriginal());

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->worldwide_quote_id),
                10
            );

            $quote->completeness = $stage->stage;

            $lock->block(30, function () use ($quote) {
                $this->connection->transaction(fn() => $quote->save());
            });

            $this->eventDispatcher->dispatch(
                new WorldwideContractQuoteMappingReviewStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote
                )
            );

        });
    }

    public function processQuoteMarginStep(WorldwideQuoteVersion $quote, ContractMarginTaxStage $stage): WorldwideQuoteVersion
    {
        $stage->distributions_margin->rewind();

        $distribution = $stage->distributions_margin->current();

//        $quote = WorldwideQuote::query()->whereHas('worldwideDistributions', function (Builder $builder) use ($distribution) {
//            $builder->whereKey($distribution->worldwide_distribution_id);
//        })->first();
//
//        if (is_null($quote)) {
//            throw (new ModelNotFoundException)->setModel(WorldwideQuote::class);
//        }

        return tap($quote, function (WorldwideQuoteVersion $quote) use ($stage) {

            $oldQuote = (new WorldwideQuote())->setRawAttributes($quote->worldwideQuote->getRawOriginal());

            $quote->completeness = $stage->stage;

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $lock->block(30, function () use ($quote) {
                $this->connection->transaction(fn() => $quote->save());
            });

            $this->eventDispatcher->dispatch(
                new WorldwideContractQuoteMarginStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote
                )
            );

        });
    }

    public function processQuoteDiscountStep(WorldwideQuoteVersion $quote, ContractDiscountStage $stage): WorldwideQuoteVersion
    {
        return tap($quote, function (WorldwideQuoteVersion $quote) use ($stage) {
            $oldQuote = (new WorldwideQuote())->setRawAttributes($quote->worldwideQuote->getRawOriginal());

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $quote->completeness = $stage->stage;

            $lock->block(30, function () use ($quote) {

                $this->connection->transaction(fn() => $quote->save());

            });

            $this->eventDispatcher->dispatch(
                new WorldwideContractQuoteDiscountStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote
                )
            );
        });
    }

    public function processContractQuoteDetailsStep(WorldwideQuoteVersion $quote, ContractDetailsStage $stage): WorldwideQuoteVersion
    {
        return tap($quote, function (WorldwideQuoteVersion $quote) use ($stage) {
            $oldQuote = (new WorldwideQuote())->setRawAttributes($quote->worldwideQuote->getRawOriginal());

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $quote->completeness = $stage->stage;

            $lock->block(30, function () use ($quote) {

                $this->connection->transaction(fn() => $quote->save());

            });

            $this->eventDispatcher->dispatch(
                new WorldwideContractQuoteDetailsStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote
                )
            );
        });
    }

    public function processQuoteSubmission(WorldwideQuoteVersion $quote, SubmitStage $stage): WorldwideQuoteVersion
    {
        return tap($quote, function (WorldwideQuoteVersion $quote) use ($stage) {
            $oldQuote = (new WorldwideQuote())->setRawAttributes($quote->worldwideQuote->getRawOriginal());

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->worldwideQuote->getKey()),
                10
            );

            $quote->completeness = ContractQuoteStage::COMPLETED;
            $quote->worldwideQuote->submitted_at = Carbon::now();
            $quote->closing_date = $stage->quote_closing_date->toDateString();
            $quote->additional_notes = $stage->additional_notes;

            $lock->block(30, function () use ($quote) {

                $this->connection->transaction(function () use ($quote) {
                    $quote->save();

                    $quote->worldwideQuote->save();
                });

            });

            $this->eventDispatcher->dispatch(
                new WorldwideQuoteSubmitted(
                    $quote->worldwideQuote,
                    $oldQuote
                )
            );
        });
    }

    public function processQuoteDraft(WorldwideQuoteVersion $quote, DraftStage $stage): WorldwideQuoteVersion
    {
        return tap($quote, function (WorldwideQuoteVersion $quote) use ($stage) {
            $oldQuote = (new WorldwideQuote())->setRawAttributes($quote->worldwideQuote->getRawOriginal());

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->worldwideQuote->getKey()),
                10
            );

            $quote->completeness = ContractQuoteStage::COMPLETED;
            $quote->worldwideQuote->submitted_at = null;
            $quote->closing_date = $stage->quote_closing_date->toDateString();
            $quote->additional_notes = $stage->additional_notes;

            $lock->block(30, function () use ($quote) {

                $this->connection->transaction(function () use ($quote) {
                    $quote->save();

                    $quote->worldwideQuote->save();
                });

            });

            $this->eventDispatcher->dispatch(
                new WorldwideQuoteDrafted(
                    $quote->worldwideQuote,
                    $oldQuote
                )
            );
        });
    }

    public function processQuoteUnravel(WorldwideQuote $quote): WorldwideQuote
    {
        return tap($quote, function (WorldwideQuote $quote) {
            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $quote->submitted_at = null;

            $lock->block(30, function () use ($quote) {

                $this->connection->transaction(fn() => $quote->save());

            });

            $this->eventDispatcher->dispatch(
                new WorldwideQuoteUnraveled(
                    $quote
                )
            );
        });
    }

    public function deleteQuote(WorldwideQuote $quote): void
    {
        $lock = $this->lockProvider->lock(
            Lock::UPDATE_WWQUOTE($quote->getKey()),
            10
        );

        $lock->block(30, function () use ($quote) {

            $this->connection->transaction(function () use ($quote) {
                $quote->versions()->getQuery()->delete();
                $quote->delete();
            });

        });

        $this->eventDispatcher->dispatch(
            new WorldwideQuoteDeleted($quote)
        );
    }

    /**
     * @inheritDoc
     */
    public function syncQuoteWithOpportunityData(WorldwideQuote $quote): void
    {
        $opportunity = $quote->opportunity;
        $activeVersion = $quote->activeVersion;

        if (!is_null($opportunity->contract_type_id) && $quote->contract_type_id !== $opportunity->contract_type_id) {

            $quote->contract_type_id = $opportunity->contract_type_id;

            $this->connection->transaction(fn() => $quote->save());
        }

        if ($quote->contract_type_id === CT_PACK && is_null($activeVersion->buy_price) && !is_null($opportunity->purchase_price)) {

            $activeVersion->buy_price = $opportunity->purchase_price;

            if (!is_null($opportunity->purchase_price_currency_code)) {
                $activeVersion->quoteCurrency()->associate(
                    Currency::query()->where('code', $opportunity->purchase_price_currency_code)->first()
                );
            }

            $this->connection->transaction(fn() => $quote->activeVersion->save());

        }

        if ($quote->contract_type_id === CT_CONTRACT) {
            /**
             * Initialization of new distributor quotes for each quote version,
             * when new suppliers of opportunity are created.
             */
            foreach ($quote->versions as $version) {

                $newSuppliers = $quote->opportunity->opportunitySuppliers()->whereDoesntHave('distributorQuotes', function (Builder $builder) use ($quote) {
                    $builder->where((new WorldwideDistribution())->worldwideQuote()->getQualifiedForeignKeyName(), $quote->activeVersion->getKey());
                })->get();

                foreach ($newSuppliers as $supplier) {

                    $this->distributionProcessor->initializeDistribution($version, $supplier->getKey());

                }

            }

            /**
             * Updating the corresponding contract distributor quotes of each supplier.
             */
            $opportunity = $quote->opportunity;

            $opportunity->opportunitySuppliers->load(['country']);

            foreach ($quote->opportunity->opportunitySuppliers as $supplier) {

                foreach ($supplier->distributorQuotes as $distributorQuote) {

                    $this->distributionProcessor->syncDistributionWithOwnOpportunitySupplier($distributorQuote);

                }
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function activateQuote(WorldwideQuote $quote): void
    {
        $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

        $quote->activated_at = now();

        $lock->block(30, function () use ($quote) {

            $this->connection->transaction(fn() => $quote->save());

        });

        $this->eventDispatcher->dispatch(
            new WorldwideQuoteActivated($quote)
        );
    }

    /**
     * @inheritDoc
     */
    public function deactivateQuote(WorldwideQuote $quote): void
    {
        $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

        $quote->activated_at = null;

        $lock->block(30, function () use ($quote) {

            $this->connection->transaction(fn() => $quote->save());

        });

        $this->eventDispatcher->dispatch(
            new WorldwideQuoteDeactivated($quote)
        );
    }

    /**
     * @inheritDoc
     */
    public function markQuoteAsDead(WorldwideQuote $quote, MarkWorldwideQuoteAsDeadData $data): void
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

        $quote->status = QuoteStatus::DEAD;
        $quote->status_reason = $data->status_reason;

        $lock->block(30, function () use ($quote) {

            $this->connection->transaction(fn() => $quote->save());

        });

        $this->eventDispatcher->dispatch(
            new WorldwideQuoteMarkedAsDead($quote)
        );
    }

    /**
     * @inheritDoc
     */
    public function markQuoteAsAlive(WorldwideQuote $quote): void
    {
        $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

        $quote->status = QuoteStatus::ALIVE;
        $quote->status_reason = null;

        $lock->block(30, function () use ($quote) {

            $this->connection->transaction(fn() => $quote->save());

        });

        $this->eventDispatcher->dispatch(
            new WorldwideQuoteMarkedAsAlive($quote)
        );
    }

    /**
     * @inheritDoc
     * @throws \Throwable
     */
    public function processQuoteReplication(WorldwideQuote $quote, User $actingUser): WorldwideQuote
    {
        $replicatedVersionData = (new WorldwideQuoteReplicator())
            ->getReplicatedVersionData($quote->activeVersion);

        $replicatedVersion = tap($replicatedVersionData->getReplicatedVersion(), function (WorldwideQuoteVersion $version) use ($actingUser) {
            $version->worldwideQuote()->disassociate();
            $version->user()->associate($actingUser);
            $version->user_version_sequence_number = 1;
        });

        $this->persistReplicatedVersionData($replicatedVersionData, $actingUser);

        return tap(new WorldwideQuote(), function (WorldwideQuote $replicatedQuote) use ($quote, $replicatedVersion, $actingUser) {
            $replicatedQuote->{$replicatedQuote->getKeyName()} = (string)Uuid::generate(4);
            $replicatedQuote->contractType()->associate($quote->contract_type_id);
            $replicatedQuote->opportunity()->associate($quote->opportunity_id);
            $replicatedQuote->user()->associate($actingUser);
            $this->assignNewQuoteNumber($replicatedQuote);
            $replicatedQuote->activeVersion()->associate($replicatedVersion);
            $replicatedVersion->worldwideQuote()->associate($replicatedQuote);
            $replicatedQuote->submitted_at = null;

            $replicatedVersion->unsetRelation('worldwideQuote');

            $lock = $this->lockProvider->lock(
                Lock::CREATE_WWQUOTE,
                10
            );

            $lock->block(30, function () use ($replicatedVersion, $replicatedQuote) {

                $this->connection->transaction(function () use ($replicatedVersion, $replicatedQuote) {
                    $replicatedQuote->save();

                    $replicatedVersion->save();
                });

            });
        });
    }

    /**
     * @param ReplicatedVersionData $replicatedVersionData
     * @param User $actingUser
     * @throws \Throwable
     */
    protected function persistReplicatedVersionData(ReplicatedVersionData $replicatedVersionData, User $actingUser): void
    {
        $version = $replicatedVersionData->getReplicatedVersion();
        $replicatedPackAssets = $replicatedVersionData->getReplicatedPackAssets();
        $replicatedDistributorQuotes = $replicatedVersionData->getReplicatedDistributorQuotes();

        $version->worldwideQuote()->disassociate();
        $version->user()->associate($actingUser);
        $version->user_version_sequence_number = 1;

        $distributorQuoteBatch = [];
        $addressDataBatch = [];
        $addressPivotBatch = [];
        $contactDataBatch = [];
        $contactPivotBatch = [];
        $mappingBatch = [];
        $distributorFileBatch = [];
        $scheduleFileBatch = [];
        $scheduleFileDataBatch = [];
        $importedRowBatch = [];
        $groupOfRowBatch = [];
        $rowOfGroupBatch = [];
        $mappedRowBatch = [];
        $packAssetBatch = array_map(fn(WorldwideQuoteAsset $asset) => $asset->getAttributes(), $replicatedPackAssets);

        foreach ($replicatedDistributorQuotes as $distributorQuoteData) {
            $distributorQuoteBatch[] = $distributorQuoteData->getDistributorQuote()->getAttributes();

            $addressDataBatch = array_merge($addressDataBatch, array_map(fn(Address $address) => $address->getAttributes(), $distributorQuoteData->getReplicatedAddressesData()->getAddressModels()));
            $addressPivotBatch = array_merge($addressPivotBatch, $distributorQuoteData->getReplicatedAddressesData()->getAddressPivots());

            $contactDataBatch = array_merge($contactDataBatch, array_map(fn(Contact $contact) => $contact->getAttributes(), $distributorQuoteData->getReplicatedContactsData()->getContactModels()));
            $contactPivotBatch = array_merge($contactPivotBatch, $distributorQuoteData->getReplicatedContactsData()->getContactPivots());

            $distributorMapping = array_map(fn(DistributionFieldColumn $fieldColumn) => $fieldColumn->getAttributes(), $distributorQuoteData->getMapping());

            $mappingBatch = array_merge($mappingBatch, $distributorMapping);

            $importedRowBatch = array_merge($importedRowBatch, array_map(fn(ImportedRow $row) => $row->getAttributes(), $distributorQuoteData->getImportedRows()));

            $distributorFile = $distributorQuoteData->getDistributorFile();

            $mappedRowBatch = array_merge($mappedRowBatch, array_map(fn(MappedRow $row) => $row->getAttributes(), $distributorQuoteData->getMappedRows()));

            $groupOfRowBatch = array_merge($groupOfRowBatch, array_map(fn(Model $model) => $model->getAttributes(), $distributorQuoteData->getRowsGroups()));

            $rowOfGroupBatch = array_merge($rowOfGroupBatch, array_merge([], ...$distributorQuoteData->getGroupRows()));

            if (!is_null($distributorFile)) {
                $distributorFileBatch[] = $distributorFile->getAttributes();
            }

            $scheduleFile = $distributorQuoteData->getScheduleFile();

            if (!is_null($scheduleFile)) {
                $scheduleFileBatch[] = $scheduleFile->getAttributes();
            }

            $scheduleFileData = $distributorQuoteData->getScheduleData();

            if (!is_null($scheduleFileData)) {
                $scheduleFileDataBatch[] = $scheduleFileData->getAttributes();
            }
        }

        $this->connection->transaction(function () use (
            $distributorQuoteBatch,
            $distributorFileBatch,
            $addressDataBatch,
            $addressPivotBatch,
            $contactDataBatch,
            $contactPivotBatch,
            $mappingBatch,
            $importedRowBatch,
            $scheduleFileBatch,
            $scheduleFileDataBatch,
            $packAssetBatch,
            $mappedRowBatch,
            $groupOfRowBatch,
            $rowOfGroupBatch,
            $version
        ) {
            $version->save();

            if (!empty($distributorFileBatch)) {
                QuoteFile::query()->insert($distributorFileBatch);
            }

            if (!empty($scheduleFileBatch)) {
                QuoteFile::query()->insert($scheduleFileBatch);
            }

            if (!empty($distributorQuoteBatch)) {
                WorldwideDistribution::query()->insert($distributorQuoteBatch);
            }

            if (!empty($addressDataBatch)) {
                Address::query()->insert($addressDataBatch);
            }

            if (!empty($addressPivotBatch)) {
                $this->connection->table((new WorldwideDistribution())->addresses()->getTable())
                    ->insert($addressPivotBatch);
            }

            if (!empty($contactDataBatch)) {
                Contact::query()->insert($contactDataBatch);
            }

            if (!empty($contactPivotBatch)) {
                $this->connection->table((new WorldwideDistribution())->contacts()->getTable())
                    ->insert($contactPivotBatch);
            }

            if (!empty($mappingBatch)) {
                DistributionFieldColumn::query()->insert($mappingBatch);
            }

            if (!empty($importedRowBatch)) {
                ImportedRow::query()->insert($importedRowBatch);
            }

            if (!empty($scheduleFileDataBatch)) {
                ScheduleData::query()->insert($scheduleFileDataBatch);
            }

            if (!empty($packAssetBatch)) {
                WorldwideQuoteAsset::query()->insert($packAssetBatch);
            }

            if (!empty($mappedRowBatch)) {
                MappedRow::query()->insert($mappedRowBatch);
            }

            if (!empty($groupOfRowBatch)) {
                DistributionRowsGroup::query()->insert($groupOfRowBatch);
            }

            if (!empty($rowOfGroupBatch)) {
                $this->connection->table((new DistributionRowsGroup())->rows()->getTable())
                    ->insert($rowOfGroupBatch);
            }
        });
    }
}
