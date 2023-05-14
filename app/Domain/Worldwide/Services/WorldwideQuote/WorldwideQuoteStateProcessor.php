<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote;

use App\Domain\Address\Enum\AddressType;
use App\Domain\Address\Models\Address;
use App\Domain\Asset\DataTransferObjects\AssetsGroupData;
use App\Domain\Asset\DataTransferObjects\GenericAssetData;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Currency\Models\Currency;
use App\Domain\DocumentMapping\Models\MappedRow;
use App\Domain\ExchangeRate\Contracts\ManagesExchangeRates;
use App\Domain\Note\Models\Note;
use App\Domain\Pipeline\Services\PipelineShortCodeResolver;
use App\Domain\Space\Services\SpaceShortCodeResolver;
use App\Domain\Sync\Enum\{Lock};
use App\Domain\User\Models\User;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Worldwide\Contracts\ProcessesWorldwideDistributionState;
use App\Domain\Worldwide\Contracts\ProcessesWorldwideQuoteState;
use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\ProcessableDistributionCollection;
use App\Domain\Worldwide\DataTransferObjects\Quote\DistributionImportData;
use App\Domain\Worldwide\DataTransferObjects\Quote\MarkWorldwideQuoteAsDeadData;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\ContractDetailsStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\ContractDiscountStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\ContractMarginTaxStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\DraftStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\ImportStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\InitStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\MappingStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\PackAssetsCreationStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\PackAssetsReviewStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\PackDetailsStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\PackDiscountStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\PackMarginTaxStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\QuoteSetupStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\ReviewStage;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\SubmitStage;
use App\Domain\Worldwide\Enum\ContractQuoteStage;
use App\Domain\Worldwide\Enum\QuoteStatus;
use App\Domain\Worldwide\Events\Quote\ProcessedImportOfDistributorQuotes;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteDetailsStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteDiscountStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteImportStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteMappingReviewStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteMappingStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteMarginStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteAssetsCreationStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteAssetsReviewStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteContactsStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteDetailsStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteDiscountStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteMarginStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteActivated;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteDeactivated;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteDeleted;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteDrafted;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteInitialized;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteMarkedAsAlive;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteMarkedAsDead;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteSubmitted;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteUnraveled;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteVersionDeleted;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteAsset;
use App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use App\Foundation\Support\Elasticsearch\Jobs\IndexSearchableEntity;
use App\Foundation\Validation\Exceptions\ValidationException;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;

class WorldwideQuoteStateProcessor implements ProcessesWorldwideQuoteState
{
    const QUOTE_NUM_FMT = "{space}-WW-{pipeline}-%'.07d";

    protected ConnectionInterface $connection;

    protected LockProvider $lockProvider;

    protected ValidatorInterface $validator;

    protected BusDispatcher $busDispatcher;

    protected ManagesExchangeRates $exchangeRateService;

    protected EventDispatcher $eventDispatcher;

    protected ProcessesWorldwideDistributionState $distributionProcessor;

    protected WorldwideQuoteVersionGuard $versionGuard;

    protected ?User $actingUser = null;

    /**
     * WorldwideQuoteStateProcessor constructor.
     *
     * @param \App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     */
    public function __construct(ConnectionInterface $connection,
        LockProvider $lockProvider,
        ValidatorInterface $validator,
        ManagesExchangeRates $exchangeRateService,
        BusDispatcher $busDispatcher,
        EventDispatcher $eventDispatcher,
        ProcessesWorldwideDistributionState $distributionProcessor,
        WorldwideQuoteVersionGuard $versionGuard)
    {
        $this->connection = $connection;
        $this->lockProvider = $lockProvider;
        $this->validator = $validator;
        $this->exchangeRateService = $exchangeRateService;
        $this->busDispatcher = $busDispatcher;
        $this->eventDispatcher = $eventDispatcher;
        $this->distributionProcessor = $distributionProcessor;
        $this->versionGuard = $versionGuard;
    }

    public function initializeQuote(InitStage $stage): WorldwideQuote
    {
        $violations = $this->validator->validate($stage);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return \tap(new WorldwideQuote(), function (WorldwideQuote $quote) use ($stage): void {
            $quoteLock = $this->lockProvider->lock(Lock::CREATE_WWQUOTE, 10);

            /** @var \App\Domain\Worldwide\Models\Opportunity $opportunity */
            $opportunity = Opportunity::query()->findOrFail($stage->opportunity_id);

            $opportunitySuppliers = $opportunity->opportunitySuppliers;

            // The lock is required for issuing a new quote number.
            $quoteLock->block(30, function () use ($stage, $quote, $opportunity, $opportunitySuppliers): void {
                $quote->{$quote->getKeyName()} = (string) Uuid::generate(4);
                $quote->contractType()->associate($stage->contract_type_id);
                $quote->opportunity()->associate($stage->opportunity_id);
                $quote->user()->associate($stage->user_id);
                $this->assignNewQuoteNumber($quote);

                /** @var \App\Domain\Worldwide\Models\WorldwideQuoteVersion $activeVersion */
                $activeVersion = \tap(new WorldwideQuoteVersion(), static function (WorldwideQuoteVersion $version) use ($opportunity, $quote, $stage): void {
                    $version->{$version->getKeyName()} = (string) Uuid::generate(4);
                    $version->user()->associate($stage->user_id);
                    $version->completeness = $stage->stage;
                    $version->quote_expiry_date = $stage->quote_expiry_date->toDateString();
                    $version->user_version_sequence_number = 1;

                    $version->payment_terms = $opportunity->customer_status;

                    if ($quote->contract_type_id === \CT_PACK) {
                        $version->buy_price = $opportunity->purchase_price;

                        if (!is_null($opportunity->purchase_price_currency_code)) {
                            $quoteCurrency = Currency::query()->where('code', $opportunity->purchase_price_currency_code)->first();
                            $buyCurrency = Currency::query()->where('code', $opportunity->purchase_price_currency_code)->first();

                            $version->quoteCurrency()->associate(
                                $quoteCurrency
                            );

                            $version->buyCurrency()->associate(
                                $buyCurrency
                            );
                        }
                    }
                });

                $quote->activeVersion()->associate($activeVersion);

                $this->connection->transaction(function () use ($activeVersion, $quote, $opportunitySuppliers): void {
                    $activeVersion->save();

                    $quote->save();

                    $activeVersion->worldwideQuote()->associate($quote)->save();

                    $activeVersion->unsetRelation('worldwideQuote');

                    if ($quote->contract_type_id !== \CT_CONTRACT) {
                        return;
                    }

                    // Populating the Distributor Quotes from Opportunity Suppliers.
                    foreach ($opportunitySuppliers as $supplier) {
                        $this->distributionProcessor->initializeDistribution($activeVersion, $supplier->getKey());
                    }
                });
            });

            $activeQuoteVersion = $quote->activeVersion;

            $this->syncQuoteWithPrimaryAccountAddressesAndContacts($activeQuoteVersion);
            $this->initializePackQuoteAssetsFromPrimaryAccountAssets($activeQuoteVersion);

            $this->busDispatcher->dispatch(
                new IndexSearchableEntity($quote)
            );

            $this->eventDispatcher->dispatch(
                new WorldwideQuoteInitialized($quote, $this->actingUser)
            );
        });
    }

    protected function initializePackQuoteAssetsFromPrimaryAccountAssets(WorldwideQuoteVersion $quoteVersion): void
    {
        /** @var Company|null $primaryAccount */
        $primaryAccount = $quoteVersion->worldwideQuote->opportunity?->primaryAccount;

        if (is_null($primaryAccount)) {
            return;
        }

        $quotesRelationship = $primaryAccount->worldwideQuotes();
        $quoteModel = new WorldwideQuote();

        /** @var \App\Domain\Worldwide\Models\WorldwideQuote[] $quotesOfPrimaryAccount */
        $quotesOfPrimaryAccount = $quotesRelationship
            ->select([
                $quoteModel->getQualifiedKeyName(),
                $quoteModel->activeVersion()->getQualifiedForeignKeyName(),
                $quoteModel->qualifyColumn('contract_type_id'),
            ])
            ->whereKeyNot($quoteVersion->worldwideQuote()->getParentKey())
            ->get();

        /** @var GenericAssetData[] $genericAssets */
        $genericAssets = [];

        foreach ($quotesOfPrimaryAccount as $quote) {
            foreach ($quote->versions as $version) {
                $genericAssets = array_merge($genericAssets, $this->collectGenericAssetDataFromQuoteVersion($version));
            }
        }

        $genericAssetDictionary = [];

        $genericAssetKeyHasher = static function (GenericAssetData $genericAsset): string {
            return md5(implode('::', [$genericAsset->vendor_reference, $genericAsset->sku, $genericAsset->serial_no]));
        };

        foreach ($genericAssets as $genericAsset) {
            $assetHash = $genericAssetKeyHasher($genericAsset);

            if (false === isset($genericAssetDictionary[$assetHash])) {
                $genericAssetDictionary[$assetHash] = $genericAsset;
            }
        }

        $newPackAssetBatch = [];

        $packAssetEntityMapper = static function (GenericAssetData $assetData, WorldwideQuoteVersion $quoteVersion): WorldwideQuoteAsset {
            static $vendorsCache = [];

            $vendorKey = $vendorsCache[$assetData->vendor_reference] ??= Vendor::query()->where('short_code', $assetData->vendor_reference)->value('id');

            return \tap(new WorldwideQuoteAsset(), static function (WorldwideQuoteAsset $quoteAsset) use ($vendorKey, $assetData, $quoteVersion): void {
                $quoteAsset->{$quoteAsset->getKeyName()} = (string) Uuid::generate(4);
                $quoteAsset->worldwideQuote()->associate($quoteVersion);

                $quoteAsset->vendor()->associate($vendorKey);
                $quoteAsset->serial_no = $assetData->serial_no;
                $quoteAsset->sku = $assetData->sku;
                $quoteAsset->product_name = $assetData->product_description;
            });
        };

        foreach ($genericAssetDictionary as $genericAsset) {
            $newPackAssetBatch[] = $packAssetEntityMapper($genericAsset, $quoteVersion)->getAttributes();
        }

        if (empty($newPackAssetBatch)) {
            return;
        }

        $this->connection->transaction(static function () use ($newPackAssetBatch): void {
            WorldwideQuoteAsset::query()->insert($newPackAssetBatch);
        });
    }

    /**
     * @return \App\Domain\Asset\DataTransferObjects\GenericAssetData[]
     */
    protected function collectGenericAssetDataFromQuoteVersion(WorldwideQuoteVersion $quoteVersion): array
    {
        $genericAssets = [];

        $vendorModel = new Vendor();
        $packAssetModel = $quoteVersion->assets()->getRelated();

        $packAssetsOfVersion = $quoteVersion->assets()->getQuery()
            ->join($vendorModel->getTable(), static function (JoinClause $join) use ($packAssetModel, $vendorModel): void {
                $join->on($vendorModel->getQualifiedKeyName(), $packAssetModel->qualifyColumn('vendor_id'))
                    ->whereNull($vendorModel->getQualifiedDeletedAtColumn());
            })
            ->whereNotNull('serial_no')
            ->whereNotNull('sku')
            ->select([
                $packAssetModel->getQualifiedKeyName(),
                $packAssetModel->qualifyColumn('serial_no'),
                $packAssetModel->qualifyColumn('sku'),
                $packAssetModel->qualifyColumn('product_name'),
                "{$vendorModel->qualifyColumn('short_code')} as vendor_short_code",
            ])
            ->get();

        foreach ($packAssetsOfVersion as $asset) {
            $genericAssets[] = new GenericAssetData([
                'vendor_reference' => $asset->vendor_short_code,
                'serial_no' => $asset->serial_no,
                'sku' => $asset->sku,
                'product_description' => $asset->product_name,
            ]);
        }

        /** @var WorldwideDistribution[] $distributorQuotes */
        $distributorQuotes = $quoteVersion->worldwideDistributions()
            ->whereHas('vendors')
            ->get();

        foreach ($distributorQuotes as $distributorQuote) {
            $contractAssetModel = $distributorQuote->mappedRows()->getRelated();

            $contractAssets = $distributorQuote->mappedRows()->getQuery()
                ->whereNotNull('serial_no')
                ->whereNotNull('product_no')
                ->select([
                    $contractAssetModel->getQualifiedKeyName(),
                    $contractAssetModel->qualifyColumn('serial_no'),
                    $contractAssetModel->qualifyColumn('product_no'),
                    $contractAssetModel->qualifyColumn('description'),
                ])
                ->get();

            foreach ($contractAssets as $mappedRow) {
                /* @var MappedRow $mappedRow */

                $genericAssets[] = new GenericAssetData([
                    'vendor_reference' => $distributorQuote->vendors[0]->short_code,
                    'serial_no' => $mappedRow->serial_no,
                    'sku' => $mappedRow->product_no,
                    'product_description' => $mappedRow->description,
                ]);
            }
        }

        return $genericAssets;
    }

    protected function syncQuoteWithPrimaryAccountAddressesAndContacts(WorldwideQuoteVersion $quoteVersion): void
    {
        $primaryAccount = $quoteVersion->worldwideQuote->opportunity?->primaryAccount;

        if (is_null($primaryAccount)) {
            return;
        }

        $addressPivots = $primaryAccount->addresses
            ->filter(static function (Address $address) {
                return $address->pivot->is_default;
            })
            ->modelKeys();

        $contactPivots = $primaryAccount->contacts
            ->filter(static function (Contact $contact) {
                return $contact->pivot->is_default;
            })
            ->modelKeys();

        $quoteLock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quoteVersion->getKey()), 10);

        $distributorQuotes = $quoteVersion->worldwideDistributions;

        $quoteLock->block(30, function () use ($quoteVersion, $distributorQuotes, $contactPivots, $addressPivots): void {
            $this->connection->transaction(static function () use ($quoteVersion, $distributorQuotes, $contactPivots, $addressPivots): void {
                if (!empty($addressPivots)) {
                    $quoteVersion->addresses()->syncWithoutDetaching($addressPivots);
                }

                if (!empty($contactPivots)) {
                    $quoteVersion->contacts()->syncWithoutDetaching($contactPivots);
                }

                foreach ($distributorQuotes as $distributorQuote) {
                    if (!empty($addressPivots)) {
                        $distributorQuote->addresses()->syncWithoutDetaching($addressPivots);
                    }

                    if (!empty($contactPivots)) {
                        $distributorQuote->contacts()->syncWithoutDetaching($contactPivots);
                    }
                }
            });
        });
    }

    protected function assignNewQuoteNumber(WorldwideQuote $quote): void
    {
        $highestNumber = $this->connection->table('worldwide_quotes')->max('sequence_number');
        $newNumber = $highestNumber + 1;

        $pipeline = $quote->opportunity->pipeline;
        $space = $pipeline->space;

        $pipelineShortCode = (new PipelineShortCodeResolver())($pipeline->pipeline_name);
        $spaceShortCode = (new SpaceShortCodeResolver())($space->space_name);

        $quoteNumberFormat = strtr(self::QUOTE_NUM_FMT, ['{space}' => $spaceShortCode, '{pipeline}' => $pipelineShortCode]);

        $quote->quote_number = sprintf($quoteNumberFormat, $newNumber);
        $quote->sequence_number = $newNumber;
    }

    public function switchActiveVersionOfQuote(WorldwideQuote $quote, WorldwideQuoteVersion $version): void
    {
        $lock = $this->lockProvider->lock(
            Lock::UPDATE_WWQUOTE($quote->getKey()),
            10
        );

        $quote->activeVersion()->associate($version);

        $lock->block(30, function () use ($quote): void {
            $this->connection->transaction(static function () use ($quote): void {
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

        $lock->block(30, function () use ($version): void {
            $this->connection->transaction(static function () use ($version): void {
                $version->delete();
            });
        });

        $this->eventDispatcher->dispatch(
            new WorldwideQuoteVersionDeleted($quote, $version, $this->actingUser)
        );
    }

    public function processQuoteSetupStep(WorldwideQuoteVersion $quote, QuoteSetupStage $stage): WorldwideQuoteVersion
    {
        if ($quote->worldwideQuote->contract_type_id !== \CT_PACK) {
            throw new ValidationException('A processing of this stage is intended for Pack Quotes only.');
        }

        $violations = $this->validator->validate($stage);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return \tap($quote, function (WorldwideQuoteVersion $quote) use ($stage): void {
            $oldQuote = $this->cloneBaseQuoteEntityFromVersion($quote);

            $quote->company()->associate($stage->company_id);
            $quote->quoteCurrency()->associate($stage->quote_currency_id);
            $quote->quoteTemplate()->associate($stage->quote_template_id);
            $quote->buy_price = $stage->buy_price;
            $quote->quote_expiry_date = $stage->quote_expiry_date->toDateString();
            $quote->payment_terms = $stage->payment_terms;
            $quote->are_end_user_addresses_available = $stage->are_end_user_addresses_available;
            $quote->are_end_user_contacts_available = $stage->are_end_user_contacts_available;
            $quote->completeness = $stage->stage;

            $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10)
                ->block(30, function () use ($quote): void {
                    $this->connection->transaction(static fn () => $quote->save());
                });

            $this->eventDispatcher->dispatch(
                new WorldwidePackQuoteContactsStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote,
                    $this->actingUser
                )
            );
        });
    }

    private function cloneBaseQuoteEntityFromVersion(WorldwideQuoteVersion $quote): WorldwideQuote
    {
        return \tap(new WorldwideQuote(), static function (WorldwideQuote $baseQuote) use ($quote): void {
            $baseQuote->setRawAttributes($quote->worldwideQuote->getRawOriginal());
            $oldVersion = (new WorldwideQuoteVersion())->setRawAttributes($quote->getRawOriginal());

            $oldVersion->setRelation('addresses', $quote->addresses);
            $oldVersion->setRelation('contacts', $quote->contacts);

            $oldDistributorQuotes = $quote->worldwideDistributions->map(static function (WorldwideDistribution $distributorQuote) {
                return \tap(new WorldwideDistribution(), static function (WorldwideDistribution $clonedDistributorQuote) use ($distributorQuote): void {
                    $clonedDistributorQuote->setRawAttributes($distributorQuote->getRawOriginal());

                    $clonedDistributorQuote->setRelation('vendors', $distributorQuote->vendors);
                    $clonedDistributorQuote->setRelation('addresses', $distributorQuote->addresses);
                    $clonedDistributorQuote->setRelation('contacts', $distributorQuote->contacts);
                    $clonedDistributorQuote->setRelation('mapping', $distributorQuote->mapping);
                });
            });

            $baseQuote->setRelation('activeVersion', $oldVersion);
            $oldVersion->setRelation('worldwideDistributions', $oldDistributorQuotes);
        });
    }

    public function processPackQuoteMarginStep(WorldwideQuoteVersion $quote, PackMarginTaxStage $stage): WorldwideQuoteVersion
    {
        $newVersionResolved = $quote->wasRecentlyCreated;

        if ($quote->worldwideQuote->contract_type_id !== \CT_PACK) {
            throw new ValidationException('A processing of this stage is intended for Pack Quotes only.');
        }

        $violations = $this->validator->validate($stage);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return \tap($quote, function (WorldwideQuoteVersion $quote) use ($stage): void {
            $oldQuote = $this->cloneBaseQuoteEntityFromVersion($quote);

            $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

            $quote->quote_type = $stage->quote_type;
            $quote->margin_method = $stage->margin_method;
            $quote->margin_value = $stage->margin_value;
            $quote->tax_value = $stage->tax_value;
            $quote->completeness = $stage->stage;

            $lock->block(30, function () use ($quote): void {
                $this->connection->transaction(static fn () => $quote->save());
            });

            $this->eventDispatcher->dispatch(
                new WorldwidePackQuoteMarginStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote,
                    $this->actingUser
                )
            );
        });
    }

    public function processPackQuoteDiscountStep(WorldwideQuoteVersion $quote, PackDiscountStage $stage): WorldwideQuoteVersion
    {
        if ($quote->worldwideQuote->contract_type_id !== \CT_PACK) {
            throw new ValidationException('A processing of this stage is intended for Pack Quotes only.');
        }

        $violations = $this->validator->validate($stage);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return \tap($quote, function (WorldwideQuoteVersion $quote) use ($stage): void {
            $oldQuote = $this->cloneBaseQuoteEntityFromVersion($quote);

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

            $lock->block(30, function () use ($quote): void {
                $this->connection->transaction(static fn () => $quote->save());
            });

            $this->eventDispatcher->dispatch(
                new WorldwidePackQuoteDiscountStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote,
                    $this->actingUser
                )
            );
        });
    }

    public function processPackQuoteDetailsStep(WorldwideQuoteVersion $quote, PackDetailsStage $stage): WorldwideQuoteVersion
    {
        if ($quote->worldwideQuote->contract_type_id !== \CT_PACK) {
            throw new ValidationException('A processing of this stage is intended for Pack Quotes only.');
        }

        $violations = $this->validator->validate($stage);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return \tap($quote, function (WorldwideQuoteVersion $quote) use ($stage): void {
            $oldQuote = $this->cloneBaseQuoteEntityFromVersion($quote);

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $quote->completeness = $stage->stage;
            $quote->pricing_document = $stage->pricing_document;
            $quote->service_agreement_id = $stage->service_agreement_id;
            $quote->system_handle = $stage->system_handle;
            $quote->additional_details = $stage->additional_details;

            $lock->block(30, function () use ($quote): void {
                $this->connection->transaction(static fn () => $quote->save());
            });

            $this->eventDispatcher->dispatch(
                new WorldwidePackQuoteDetailsStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote,
                    $this->actingUser
                )
            );
        });
    }

    public function processQuoteAssetsCreationStep(WorldwideQuoteVersion $quote, PackAssetsCreationStage $stage): WorldwideQuoteVersion
    {
        $violations = $this->validator->validate($stage);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        return \tap($quote, function (WorldwideQuoteVersion $quote) use ($stage): void {
            $oldQuote = $this->cloneBaseQuoteEntityFromVersion($quote);

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $quote->completeness = $stage->stage;

            $lock->block(30, function () use ($quote): void {
                $this->connection->transaction(
                    static fn () => $quote->save()
                );
            });

            $this->eventDispatcher->dispatch(
                new WorldwidePackQuoteAssetsCreationStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote,
                    $this->actingUser
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

        return \tap($quote, function (WorldwideQuoteVersion $quote) use ($stage): void {
            $oldQuote = $this->cloneBaseQuoteEntityFromVersion($quote);

            $newVersionResolved = $quote->wasRecentlyCreated;

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $quote->completeness = $stage->stage;
            $quote->use_groups = $stage->use_groups;

            if ($stage->use_groups) {
                $quote->sort_assets_groups_column = $stage->sort_assets_groups_column;
                $quote->sort_assets_groups_direction = $stage->sort_assets_groups_direction;
            } else {
                $quote->sort_rows_column = $stage->sort_rows_column;
                $quote->sort_rows_direction = $stage->sort_rows_direction;
            }

            $lock->block(30, function () use ($quote, $stage, $newVersionResolved): void {
                $this->connection->transaction(static function () use ($quote, $stage, $newVersionResolved): void {
                    if ($stage->use_groups) {
                        $quote->assetsGroups()->update(['is_selected' => false]);

                        $quote->assetsGroups()->getQuery()
                            ->when($stage->reject,
                                static function (Builder $builder) use ($stage, $newVersionResolved) {
                                    if ($newVersionResolved) {
                                        return $builder->whereNotIn('replicated_assets_group_id', $stage->selected_groups);
                                    }

                                    return $builder->whereKeyNot($stage->selected_groups);
                                }, static function (Builder $builder) use ($stage, $newVersionResolved) {
                                    if ($newVersionResolved) {
                                        return $builder->whereIn('replicated_assets_group_id', $stage->selected_groups);
                                    }

                                    return $builder->whereKey($stage->selected_groups);
                                })
                            ->update(['is_selected' => true]);

                        $quote->save();

                        return;
                    }

                    $quote->assets()->update(['is_selected' => false]);

                    $quote->assets()
                        ->when($stage->reject, static function (Builder $builder) use ($stage, $newVersionResolved) {
                            if ($newVersionResolved) {
                                return $builder->whereNotIn('replicated_asset_id', $stage->selected_rows);
                            }

                            return $builder->whereKeyNot($stage->selected_rows);
                        }, static function (Builder $builder) use ($newVersionResolved, $stage) {
                            if ($newVersionResolved) {
                                return $builder->whereIn('replicated_asset_id', $stage->selected_rows);
                            }

                            return $builder->whereKey($stage->selected_rows);
                        })
                        ->update(['is_selected' => true]);

                    $quote->save();
                });
            });

            $this->eventDispatcher->dispatch(
                new WorldwidePackQuoteAssetsReviewStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote,
                    $this->actingUser
                )
            );
        });
    }

    public function processQuoteImportStep(WorldwideQuoteVersion $quoteVersion, ImportStage $stage): WorldwideQuoteVersion
    {
        $newVersionResolved = $quoteVersion->wasRecentlyCreated;

        if ($quoteVersion->worldwideQuote->contract_type_id !== \CT_CONTRACT) {
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

        $oldQuote = $this->cloneBaseQuoteEntityFromVersion($quoteVersion);

        $opportunity = $quoteVersion->worldwideQuote->opportunity;
        $primaryAccount = $opportunity->primaryAccount;

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
        $quoteVersion->are_end_user_addresses_available = $stage->are_end_user_addresses_available;
        $quoteVersion->are_end_user_contacts_available = $stage->are_end_user_contacts_available;

        $lock->block(30, static fn () => $quoteVersion->saveOrFail());

        /** @var \App\Domain\Worldwide\Models\WorldwideDistribution[]|Collection $distributionModels */
        $distributionModels = $quoteVersion->worldwideDistributions()
            ->when($newVersionResolved, static function (Builder $builder) use ($distributionsDataDictionary): void {
                $builder->whereIn('replicated_distributor_quote_id', array_keys($distributionsDataDictionary));
            }, static function (Builder $builder) use ($distributionsDataDictionary): void {
                $builder->whereKey(array_keys($distributionsDataDictionary));
            })
            ->get();

        $distributionModels = \value(static function () use ($distributionModels, $newVersionResolved): array {
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
            $model->buyCurrency()->associate($distributionData->buy_currency_id);
            $model->buy_price = $distributionData->buy_price;
            $model->calculate_list_price = $distributionData->calculate_list_price;
            $model->distribution_expiry_date = $distributionData->distribution_expiry_date;
            $model->distribution_currency_quote_currency_exchange_rate_margin = $distributionData->distribution_currency_quote_currency_exchange_rate_margin;
            $model->distribution_currency_quote_currency_exchange_rate_value = $distributionData->distribution_currency_quote_currency_exchange_rate_value;
            $model->file_date_format = $distributionData->file_date_format;

            $lock->block(30, function () use ($model, $distributionData): void {
                $this->connection->transaction(static function () use ($model, $distributionData): void {
                    $model->save();

                    $model->vendors()->sync($distributionData->vendors);
                    $model->addresses()->sync($distributionData->address_ids);
                    $model->contacts()->sync($distributionData->contact_ids);
                });
            });
        }

        // Updating computed price of mapped rows of the quote distributions.
        $distributions = $quoteVersion->worldwideDistributions()
            ->with('distributionCurrency')
            ->get(['id',
                'worldwide_quote_id',
                'distributor_file_id',
                'distribution_currency_id',
                'distribution_exchange_rate',
                'distribution_currency_quote_currency_exchange_rate_value',
                'distribution_currency_quote_currency_exchange_rate_margin',
                'created_at',
                'updated_at']);

        $distributions->each(function (WorldwideDistribution $distribution) use ($quoteVersion): void {
            $appliedExchangeRateValue = \with($distribution, static function (WorldwideDistribution $distribution) use ($quoteVersion) {
                if ($quoteVersion->quoteCurrency->is($distribution->distributionCurrency)) {
                    return 1.0;
                }

                $exchangeRateValue = (float) $distribution->distribution_currency_quote_currency_exchange_rate_value;
                $exchangeRateMargin = (float) ($distribution->distribution_currency_quote_currency_exchange_rate_margin ?? 0.0) / 100;

                return $exchangeRateValue + ($exchangeRateValue * $exchangeRateMargin);
            });

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWDISTRIBUTION($distribution->getKey()),
                10
            );

            $lock->block(30, function () use ($distribution, $appliedExchangeRateValue): void {
                $this->connection->transaction(static function () use ($distribution, $appliedExchangeRateValue): void {
                    $distribution->mappedRows()->update(['price' => DB::raw("original_price * $appliedExchangeRateValue")]);
                });
            });
        });

        return \tap($quoteVersion, function (WorldwideQuoteVersion $quote) use ($oldQuote): void {
            $this->busDispatcher->dispatch(
                new IndexSearchableEntity($quote->worldwideQuote)
            );

            $this->eventDispatcher->dispatch(
                new WorldwideContractQuoteImportStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote,
                    $this->actingUser
                )
            );
        });
    }

    public function processQuoteMappingStep(WorldwideQuoteVersion $quote, MappingStage $stage): WorldwideQuoteVersion
    {
        $stage->mapping->rewind();

        return \tap($quote, function (WorldwideQuoteVersion $quote) use ($stage): void {
            $oldQuote = $this->cloneBaseQuoteEntityFromVersion($quote);

            $this->distributionProcessor->processDistributionsMapping($quote, $stage->mapping);

            $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->worldwide_quote_id), 10);

            $quote->completeness = $stage->stage;

            $lock->block(30, static function () use ($quote): void {
                $quote->save();
            });

            $this->eventDispatcher->dispatch(
                new WorldwideContractQuoteMappingStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote,
                    $this->actingUser
                )
            );
        });
    }

    public function processQuoteMappingReviewStep(WorldwideQuoteVersion $quote, ReviewStage $stage): WorldwideQuoteVersion
    {
        return \tap($quote, function (WorldwideQuoteVersion $quote) use ($stage): void {
            $oldQuote = $this->cloneBaseQuoteEntityFromVersion($quote);

            $this->distributionProcessor->updateRowsSelection($quote, $stage->selected_distribution_rows);

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->worldwide_quote_id),
                10
            );

            $quote->completeness = $stage->stage;

            $lock->block(30, function () use ($quote): void {
                $this->connection->transaction(static fn () => $quote->save());
            });

            $this->eventDispatcher->dispatch(
                new WorldwideContractQuoteMappingReviewStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote,
                    $this->actingUser
                )
            );
        });
    }

    public function processQuoteMarginStep(WorldwideQuoteVersion $quote, ContractMarginTaxStage $stage): WorldwideQuoteVersion
    {
        $stage->distributions_margin->rewind();

        return \tap($quote, function (WorldwideQuoteVersion $quote) use ($stage): void {
            $oldQuote = $this->cloneBaseQuoteEntityFromVersion($quote);

            $this->distributionProcessor->setDistributionsMargin($quote, $stage->distributions_margin);

            $quote->completeness = $stage->stage;

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $lock->block(30, function () use ($quote): void {
                $this->connection->transaction(static fn () => $quote->save());
            });

            $this->eventDispatcher->dispatch(
                new WorldwideContractQuoteMarginStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote,
                    $this->actingUser
                )
            );
        });
    }

    public function processQuoteDiscountStep(WorldwideQuoteVersion $quote, ContractDiscountStage $stage): WorldwideQuoteVersion
    {
        return \tap($quote, function (WorldwideQuoteVersion $quote) use ($stage): void {
            $oldQuote = $this->cloneBaseQuoteEntityFromVersion($quote);

            $this->distributionProcessor->applyDistributionsDiscount($quote, $stage->distributionDiscounts);

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $quote->completeness = $stage->stage;

            $lock->block(30, function () use ($quote): void {
                $this->connection->transaction(static fn () => $quote->save());
            });

            $this->eventDispatcher->dispatch(
                new WorldwideContractQuoteDiscountStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote,
                    $this->actingUser
                )
            );
        });
    }

    public function processContractQuoteDetailsStep(WorldwideQuoteVersion $quote, ContractDetailsStage $stage): WorldwideQuoteVersion
    {
        $violations = $this->validator->validate($stage);

        if (count($violations)) {
            throw new ValidationFailedException($stage, $violations);
        }

        return \tap($quote, function (WorldwideQuoteVersion $quote) use ($stage): void {
            $oldQuote = $this->cloneBaseQuoteEntityFromVersion($quote);

            $this->distributionProcessor->updateDistributionsDetails(
                $quote,
                $stage->distributionDetailsCollection
            );

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $quote->completeness = $stage->stage;

            $lock->block(30, function () use ($quote): void {
                $this->connection->transaction(static fn () => $quote->save());
            });

            $this->eventDispatcher->dispatch(
                new WorldwideContractQuoteDetailsStepProcessed(
                    $quote->worldwideQuote,
                    $oldQuote,
                    $this->actingUser
                )
            );
        });
    }

    public function processQuoteSubmission(WorldwideQuoteVersion $quote, SubmitStage $stage): WorldwideQuoteVersion
    {
        return \tap($quote, function (WorldwideQuoteVersion $quote) use ($stage): void {
            $oldQuote = (new WorldwideQuote())->setRawAttributes($quote->worldwideQuote->getRawOriginal());

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->worldwideQuote->getKey()),
                10
            );

            $quote->completeness = ContractQuoteStage::COMPLETED;
            $quote->worldwideQuote->submitted_at = Carbon::now();
            $quote->closing_date = $stage->quote_closing_date->toDateString();
            $quote->additional_notes = $stage->additional_notes;
            $defaultInvoiceAddress = $quote->worldwideQuote->opportunity->primaryAccount
                ->addresses
                ->lazy()
                ->where('pivot.is_default', true)
                ->whereStrict('address_type', AddressType::INVOICE)
                ->first();

            if ($defaultInvoiceAddress instanceof Address) {
                $quote->submittedPaInvoiceAddress()->associate($this->replicateAddress($defaultInvoiceAddress));
            }

            /** @var \App\Domain\Note\Models\Note $quoteNote */
            $quoteNote = \tap($quote->submitNote ?? new Note(), static function (Note $note) use ($quote, $stage): void {
                if (false === $note->exists) {
                    $note->{$note->getKeyName()} = (string) Uuid::generate(4);
                }

                $note->owner()->associate($quote->user()->getParentKey());

                if (false === $note->getFlag(Note::FROM_ENTITY_WIZARD_SUBMIT)) {
                    $note->flags |= Note::FROM_ENTITY_WIZARD_SUBMIT;
                }

                $note->note = $stage->additional_notes;
            });

            $lock->block(30, function () use ($quoteNote, $quote): void {
                $this->connection->transaction(static function () use ($quoteNote, $quote): void {
                    $quote->save();

                    $quote->worldwideQuote->save();

                    // Persist the WorldwideQuoteNote entity if there is any text only,
                    // otherwise the entity will be deleted if it exists.
                    if (blank_html($quoteNote->note)) {
                        if ($quoteNote->exists) {
                            $quoteNote->delete();
                        }
                    } else {
                        $quoteNote->save();

                        $quoteNote->worldwideQuotesHaveNote()->sync($quote->worldwideQuote);
                        $quoteNote->worldwideQuoteVersionsHaveNote()->sync($quote);
                        $quoteNote->opportunitiesHaveNote()->sync($quote->worldwideQuote->opportunity);
                    }
                });
            });

            $this->eventDispatcher->dispatch(
                new WorldwideQuoteSubmitted(
                    $quote->worldwideQuote,
                    $oldQuote,
                    $this->actingUser
                )
            );
        });
    }

    private function replicateAddress(Address $address): Address
    {
        return tap($address->replicate(), static function (Address $address): void {
            $address->{$address->getDeletedAtColumn()} = $address->freshTimestamp();
            $address->save();
        });
    }

    public function processQuoteDraft(WorldwideQuoteVersion $quote, DraftStage $stage): WorldwideQuoteVersion
    {
        return \tap($quote, function (WorldwideQuoteVersion $quote) use ($stage): void {
            $oldQuote = $this->cloneBaseQuoteEntityFromVersion($quote);

            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->worldwideQuote->getKey()),
                10
            );

            $quote->completeness = ContractQuoteStage::COMPLETED;
            $quote->worldwideQuote->submitted_at = null;
            $quote->closing_date = $stage->quote_closing_date->toDateString();
            $quote->additional_notes = $stage->additional_notes;

            /** @var Note $quoteNote */
            $quoteNote = \tap($quote->draftNote ?? new Note(), static function (Note $note) use ($quote, $stage): void {
                if (false === $note->exists) {
                    $note->{$note->getKeyName()} = (string) Uuid::generate(4);
                }

                $note->owner()->associate($quote->user()->getParentKey());

                if (false === $note->getFlag(Note::FROM_ENTITY_WIZARD_DRAFT)) {
                    $note->flags |= Note::FROM_ENTITY_WIZARD_DRAFT;
                }

                $note->note = $stage->additional_notes;
            });

            $lock->block(30, function () use ($quoteNote, $quote): void {
                $this->connection->transaction(static function () use ($quoteNote, $quote): void {
                    $quote->save();

                    $quote->worldwideQuote->save();

                    // Persist the WorldwideQuoteNote entity if there is any text only,
                    // otherwise the entity will be deleted if it exists.
                    if (blank_html($quoteNote->note)) {
                        if ($quoteNote->exists) {
                            $quoteNote->delete();
                        }
                    } else {
                        $quoteNote->save();

                        $quoteNote->worldwideQuotesHaveNote()->sync($quote->worldwideQuote);
                        $quoteNote->worldwideQuoteVersionsHaveNote()->sync($quote);
                        $quoteNote->opportunitiesHaveNote()->sync($quote->worldwideQuote->opportunity);
                    }
                });
            });

            $this->eventDispatcher->dispatch(
                new WorldwideQuoteDrafted(
                    $quote->worldwideQuote,
                    $oldQuote,
                    $this->actingUser
                )
            );
        });
    }

    public function processQuoteUnravel(WorldwideQuote $quote): WorldwideQuote
    {
        return \tap($quote, function (WorldwideQuote $quote): void {
            $lock = $this->lockProvider->lock(
                Lock::UPDATE_WWQUOTE($quote->getKey()),
                10
            );

            $quote->submitted_at = null;

            $lock->block(30, function () use ($quote): void {
                $this->connection->transaction(static fn () => $quote->save());
            });

            $this->eventDispatcher->dispatch(
                new WorldwideQuoteUnraveled(
                    $quote,
                    $this->actingUser
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

        $lock->block(30, function () use ($quote): void {
            $this->connection->transaction(static function () use ($quote): void {
                $quote->versions()->getQuery()->delete();
                $quote->delete();
            });
        });

        $this->eventDispatcher->dispatch(
            new WorldwideQuoteDeleted($quote, $this->actingUser)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function syncQuoteWithOpportunityData(WorldwideQuote $quote): void
    {
        $opportunity = $quote->opportunity;
        $primaryAccount = $opportunity->primaryAccount;
        $quote->load(['versions.addresses', 'versions.contacts']);

        if (!is_null($opportunity->contract_type_id) && $quote->contract_type_id !== $opportunity->contract_type_id) {
            $quote->contract_type_id = $opportunity->contract_type_id;

            $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

            $lock->block(30, function () use ($quote): void {
                $this->connection->transaction(static fn () => $quote->save());
            });
        }

        $quoteCurrency = Currency::query()->where('code', $opportunity->purchase_price_currency_code)->first();
        $buyCurrency = Currency::query()->where('code', $opportunity->purchase_price_currency_code)->first();

        // Populate attributes from opportunity to each version of the quote entity.
        foreach ($quote->versions as $version) {
            if (!is_null($opportunity->customer_status)) {
                $version->payment_terms = $opportunity->customer_status;
            }

            $version->buy_price = $opportunity->purchase_price;

            if (!$version->quoteCurrency->exists) {
                $version->quoteCurrency()->associate(
                    $quoteCurrency
                );
            }

            if (!$version->buyCurrency->exists) {
                $version->buyCurrency()->associate(
                    $buyCurrency
                );
            }
        }

        $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

        $lock->block(30, function () use ($quote): void {
            $this->connection->transaction(static function () use ($quote): void {
                foreach ($quote->versions as $version) {
                    $version->save();
                }
            });
        });

        /** @var Collection $addressModelsOfPrimaryAccount */
        $addressModelsOfPrimaryAccount = \value(static function () use ($primaryAccount): Collection {
            if (is_null($primaryAccount)) {
                return new Collection();
            }

            return $primaryAccount->addresses;
        });

        /** @var Collection $contactModelsOfPrimaryAccount */
        $contactModelsOfPrimaryAccount = \value(static function () use ($primaryAccount): Collection {
            if (is_null($primaryAccount)) {
                return new Collection();
            }

            return $primaryAccount->contacts;
        });

        $addressModelKeysOfPrimaryAccount = $addressModelsOfPrimaryAccount->modelKeys();
        $contactModelKeysOfPrimaryAccount = $contactModelsOfPrimaryAccount->modelKeys();

        $defaultAddressModelKeysOfPrimaryAccount = $addressModelsOfPrimaryAccount->filter(static fn (Address $address) => $address->pivot->is_default)->modelKeys();
        $defaultContactModelKeysOfPrimaryAccount = $contactModelsOfPrimaryAccount->filter(static fn (Contact $contact) => $contact->pivot->is_default)->modelKeys();

        if ($quote->contract_type_id === \CT_PACK) {
            $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

            $lock->block(30,
                fn () => $this->connection->transaction(static function () use ($quote, $addressModelKeysOfPrimaryAccount, $contactModelKeysOfPrimaryAccount, $defaultAddressModelKeysOfPrimaryAccount, $defaultContactModelKeysOfPrimaryAccount): void {
                    foreach ($quote->versions as $version) {
                        // Detaching of addresses which are not present in the primary account entity.
                        if (!empty($addressModelKeysOfPrimaryAccount)) {
                            $version->addresses()
                                ->newPivotQuery()
                                ->whereNotIn($version->addresses()->getQualifiedRelatedPivotKeyName(), $addressModelKeysOfPrimaryAccount)
                                ->delete();
                        }

                        // Detaching of contacts which are not present in the primary account entity.
                        if (!empty($contactModelKeysOfPrimaryAccount)) {
                            $version->contacts()
                                ->newPivotQuery()
                                ->whereNotIn($version->contacts()->getQualifiedRelatedPivotKeyName(), $contactModelKeysOfPrimaryAccount)
                                ->delete();
                        }

                        // Attach default addresses of primary account,
                        // if a version doesn't have any addresses.
                        if ($version->addresses->isEmpty()) {
                            $version->addresses()->sync($defaultAddressModelKeysOfPrimaryAccount);
                        }

                        // Attach default contacts of primary account,
                        // if a version doesn't have any contact.
                        if ($version->contacts->isEmpty()) {
                            $version->contacts()->sync($defaultContactModelKeysOfPrimaryAccount);
                        }

                        $version->save();
                    }
                }));
        }

        if ($quote->contract_type_id === \CT_CONTRACT) {
            /*
             * Initialization of new distributor quotes for each quote version,
             * when new suppliers of opportunity are created.
             */
            foreach ($quote->versions as $version) {
                $newSuppliers = $quote->opportunity->opportunitySuppliers()->whereDoesntHave('distributorQuotes', static function (Builder $builder) use ($quote): void {
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
                    // Detaching of addresses & contacts which are not present in the primary account entity.
                    if ($addressModelsOfPrimaryAccount->isNotEmpty()) {
                        $distributorQuote->addresses()
                            ->newPivotQuery()
                            ->whereNotIn($distributorQuote->addresses()->getQualifiedRelatedPivotKeyName(), $addressModelsOfPrimaryAccount->modelKeys())
                            ->delete();
                    }

                    if ($contactModelsOfPrimaryAccount->isNotEmpty()) {
                        $distributorQuote->contacts()
                            ->newPivotQuery()
                            ->whereNotIn($distributorQuote->contacts()->getQualifiedRelatedPivotKeyName(), $contactModelsOfPrimaryAccount->modelKeys())
                            ->delete();
                    }

                    $this->distributionProcessor->syncDistributionWithOwnOpportunitySupplier($distributorQuote);
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function activateQuote(WorldwideQuote $quote): void
    {
        $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

        $quote->activated_at = \now();

        $lock->block(30, function () use ($quote): void {
            $this->connection->transaction(static fn () => $quote->save());
        });

        $this->eventDispatcher->dispatch(
            new WorldwideQuoteActivated($quote, $this->actingUser)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function deactivateQuote(WorldwideQuote $quote): void
    {
        $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

        $quote->activated_at = null;

        $lock->block(30, function () use ($quote): void {
            $this->connection->transaction(static fn () => $quote->save());
        });

        $this->eventDispatcher->dispatch(
            new WorldwideQuoteDeactivated($quote, $this->actingUser)
        );
    }

    /**
     * {@inheritDoc}
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

        $lock->block(30, function () use ($quote): void {
            $this->connection->transaction(static fn () => $quote->save());
        });

        $this->eventDispatcher->dispatch(
            new WorldwideQuoteMarkedAsDead($quote, $this->actingUser)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function markQuoteAsAlive(WorldwideQuote $quote): void
    {
        $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

        $quote->status = QuoteStatus::ALIVE;
        $quote->status_reason = null;

        $lock->block(30, function () use ($quote): void {
            $this->connection->transaction(static fn () => $quote->save());
        });

        $this->eventDispatcher->dispatch(
            new WorldwideQuoteMarkedAsAlive($quote, $this->actingUser)
        );
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Throwable
     */
    public function processQuoteReplication(WorldwideQuote $quote, User $actingUser): WorldwideQuote
    {
        $replicatedVersionData = (new WorldwideQuoteReplicator())
            ->getReplicatedVersionData($quote->activeVersion);

        /** @var \App\Domain\Worldwide\Models\WorldwideQuoteVersion $replicatedVersion */
        $replicatedVersion = \tap($replicatedVersionData->getReplicatedVersion(), static function (WorldwideQuoteVersion $version) use ($actingUser): void {
            $version->worldwideQuote()->disassociate();
            $version->user()->associate($actingUser);
            $version->user_version_sequence_number = 1;
        });

        $this->versionGuard->persistReplicatedVersionData($replicatedVersionData);

        return \tap(new WorldwideQuote(), function (WorldwideQuote $replicatedQuote) use ($quote, $replicatedVersion, $actingUser): void {
            $replicatedQuote->{$replicatedQuote->getKeyName()} = (string) Uuid::generate(4);
            $replicatedQuote->contractType()->associate($quote->contract_type_id);
            $replicatedQuote->opportunity()->associate($quote->opportunity_id);
            $replicatedQuote->user()->associate($actingUser);
            $this->assignNewQuoteNumber($replicatedQuote);
            $replicatedQuote->activeVersion()->associate($replicatedVersion);
            $replicatedVersion->worldwideQuote()->associate($replicatedQuote);
            $replicatedQuote->submitted_at = null;

            $quoteNote = $replicatedVersion->note;

            if (!is_null($quoteNote)) {
                $quoteNote->owner()->associate($actingUser);
            }

            $replicatedVersion->unsetRelation('worldwideQuote');

            $lock = $this->lockProvider->lock(
                Lock::CREATE_WWQUOTE,
                10
            );

            $lock->block(30, function () use ($replicatedVersion, $replicatedQuote, $quoteNote): void {
                $this->connection->transaction(static function () use ($replicatedVersion, $replicatedQuote, $quoteNote): void {
                    $replicatedQuote->save();

                    $replicatedVersion->save();

                    if (!is_null($quoteNote)) {
                        $quoteNote->save();

                        $quoteNote->worldwideQuotesHaveNote()->sync($replicatedQuote);
                        $quoteNote->worldwideQuoteVersionsHaveNote()->sync($replicatedVersion);
                    }
                });
            });

            $this->busDispatcher->dispatch(
                new IndexSearchableEntity($replicatedQuote)
            );
        });
    }

    /**
     * {@inheritDoc}
     */
    public function processImportOfDistributorQuotes(WorldwideQuoteVersion $version, ProcessableDistributionCollection $collection): void
    {
        $oldQuote = $this->cloneBaseQuoteEntityFromVersion($version);

        $this->distributionProcessor->processDistributionsImport($version, $collection);

        $this->eventDispatcher->dispatch(
            new ProcessedImportOfDistributorQuotes(
                $version->worldwideQuote,
                $oldQuote,
                $this->actingUser
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function setActingUser(User $user = null): ProcessesWorldwideQuoteState
    {
        return \tap($this, function () use ($user): void {
            $this->actingUser = $user;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function createGroupOfAssets(WorldwideQuoteVersion $quote, AssetsGroupData $data): WorldwideQuoteAssetsGroup
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        $data->assets = \value(static function () use ($data, $quote): array {
            if ($quote->wasRecentlyCreated) {
                return $quote->assets()->whereIn('replicated_asset_id', $data->assets)
                    ->pluck($quote->assets()->getRelated()->getQualifiedKeyName())
                    ->all();
            }

            return $data->assets;
        });

        return \tap(new WorldwideQuoteAssetsGroup(), function (WorldwideQuoteAssetsGroup $assetsGroup) use ($quote, $data): void {
            $assetsGroup->{$assetsGroup->getKeyName()} = (string) Uuid::generate(4);
            $assetsGroup->worldwideQuoteVersion()->associate($quote);
            $assetsGroup->group_name = $data->group_name;
            $assetsGroup->search_text = $data->search_text;

            $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

            $lock->block(30, function () use ($assetsGroup, $data): void {
                $this->connection->transaction(static function () use ($data, $assetsGroup): void {
                    $assetsGroup->save();

                    $assetsGroup->assets()->sync($data->assets);
                });
            });
        });
    }

    /**
     * {@inheritDoc}
     */
    public function updateGroupOfAssets(WorldwideQuoteVersion $quote,
        WorldwideQuoteAssetsGroup $assetsGroup,
        AssetsGroupData $data): WorldwideQuoteAssetsGroup
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationFailedException($data, $violations);
        }

        /** @var \App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup $assetsGroup */
        $assetsGroup = \value(static function () use ($assetsGroup, $quote): WorldwideQuoteAssetsGroup {
            if ($quote->wasRecentlyCreated) {
                return $quote->assetsGroups()->where('replicated_assets_group_id', $assetsGroup->getKey())->sole();
            }

            return $assetsGroup;
        });

        $data->assets = \value(static function () use ($data, $quote): array {
            if ($quote->wasRecentlyCreated) {
                return $quote->assets()->whereIn('replicated_asset_id', $data->assets)
                    ->pluck($quote->assets()->getRelated()->getQualifiedKeyName())
                    ->all();
            }

            return $data->assets;
        });

        return \tap($assetsGroup, function (WorldwideQuoteAssetsGroup $assetsGroup) use ($quote, $data): void {
            $assetsGroup->group_name = $data->group_name;
            $assetsGroup->search_text = $data->search_text;

            $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

            $lock->block(30, function () use ($assetsGroup, $data): void {
                $this->connection->transaction(static function () use ($data, $assetsGroup): void {
                    $assetsGroup->save();

                    $assetsGroup->assets()->sync($data->assets);
                });
            });
        });
    }

    /**
     * {@inheritDoc}
     */
    public function deleteGroupOfAssets(WorldwideQuoteVersion $quote, WorldwideQuoteAssetsGroup $assetsGroup): void
    {
        /** @var \App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup $assetsGroup */
        $assetsGroup = \value(static function () use ($assetsGroup, $quote): WorldwideQuoteAssetsGroup {
            if ($quote->wasRecentlyCreated) {
                return $quote->assetsGroups()->where('replicated_assets_group_id', $assetsGroup->getKey())->sole();
            }

            return $assetsGroup;
        });

        $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

        $lock->block(30, function () use ($assetsGroup): void {
            $this->connection->transaction(static function () use ($assetsGroup): void {
                $assetsGroup->delete();
            });
        });
    }

    /**
     * {@inheritDoc}
     */
    public function moveAssetsBetweenGroupsOfAssets(WorldwideQuoteVersion $quote,
        WorldwideQuoteAssetsGroup $outputAssetsGroup,
        WorldwideQuoteAssetsGroup $inputAssetsGroup,
        array $assets): void
    {
        /** @var WorldwideQuoteAssetsGroup $outputAssetsGroup */
        $outputAssetsGroup = \value(static function () use ($quote, $outputAssetsGroup): WorldwideQuoteAssetsGroup {
            if ($quote->wasRecentlyCreated) {
                return $quote->assetsGroups()->where('replicated_assets_group_id', $outputAssetsGroup->getKey())->sole();
            }

            return $outputAssetsGroup;
        });

        /** @var \App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup $inputAssetsGroup */
        $inputAssetsGroup = \value(static function () use ($quote, $inputAssetsGroup): WorldwideQuoteAssetsGroup {
            if ($quote->wasRecentlyCreated) {
                return $quote->assetsGroups()->where('replicated_assets_group_id', $inputAssetsGroup->getKey())->sole();
            }

            return $inputAssetsGroup;
        });

        /** @var array $assets */
        $assets = \value(static function () use ($quote, $outputAssetsGroup, $assets): array {
            if ($quote->wasRecentlyCreated) {
                return $outputAssetsGroup->assets()->whereIn('replicated_asset_id', $assets)->pluck((new WorldwideQuoteAsset())->getQualifiedKeyName())->all();
            }

            return $assets;
        });

        $lock = $this->lockProvider->lock(Lock::UPDATE_WWQUOTE($quote->getKey()), 10);

        $lock->block(30, function () use ($inputAssetsGroup, $assets, $outputAssetsGroup): void {
            $this->connection->transaction(static function () use ($assets, $inputAssetsGroup, $outputAssetsGroup): void {
                $outputAssetsGroup->assets()->detach($assets);
                $inputAssetsGroup->assets()->syncWithoutDetaching($assets);
            });
        });
    }
}
