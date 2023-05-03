<?php

namespace App\Domain\Worldwide\Listeners;

use App\Domain\Activity\Services\ActivityLogger;
use App\Domain\Activity\Services\ChangesDetector;
use App\Domain\Address\Models\Address;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Currency\Models\Currency;
use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Discount\Models\SND;
use App\Domain\Note\DataTransferObjects\CreateNoteData;
use App\Domain\Note\Models\Note;
use App\Domain\Note\Services\NoteEntityService;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\User\Services\ApplicationUserResolver;
use App\Domain\Worldwide\Enum\ContractQuoteStage;
use App\Domain\Worldwide\Enum\PackQuoteStage;
use App\Domain\Worldwide\Events\Quote\NewVersionOfWorldwideQuoteCreated;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteDetailsStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteDiscountStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteImportStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteMappingReviewStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideContractQuoteMappingStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteAssetsCreationStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteAssetsReviewStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteContactsStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteDetailsStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteDiscountStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwidePackQuoteMarginStepProcessed;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteDeleted;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteDrafted;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteFilesExported;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteInitialized;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteOwnershipChanged;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteSubmitted;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteUnraveled;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteVersionDeleted;
use App\Domain\Worldwide\Models\DistributionFieldColumn;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use App\Domain\Worldwide\Notifications\WorldwideQuoteOwnershipChangedNotification;
use App\Domain\Worldwide\Notifications\WorldwideQuoteSubmittedNotification;
use App\Domain\Worldwide\Notifications\WorldwideQuoteUnraveledNotification;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteAttachmentService;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Str;

class WorldwideQuoteEventAuditor implements ShouldQueue
{
    public function __construct(
        protected readonly Config $config,
        protected readonly BusDispatcher $busDispatcher,
        protected readonly ActivityLogger $activityLogger,
        protected readonly ChangesDetector $changesDetector,
        protected readonly NoteEntityService $noteEntityService,
        protected readonly ApplicationUserResolver $appUserResolver,
        protected readonly WorldwideQuoteAttachmentService $quoteAttachmentService,
    ) {
    }

    public function subscribe(\Illuminate\Events\Dispatcher $events): array
    {
        return [
            WorldwideQuoteInitialized::class => [
                static::handleInitializedEvent(...),
                static::createNoteForInitializedQuote(...),
            ],
            WorldwideQuoteSubmitted::class => [
                static::auditSubmittedEvent(...),
                static::createNoteForSubmittedQuote(...),
                static::createAttachmentFromSubmittedQuote(...),
                static::createAttachmentFromDistributorFiles(...),
                static::notifyAboutQuoteSubmitted(...),
            ],
            WorldwideQuoteUnraveled::class => [
                static::auditUnraveledEvent(...),
                static::notifyAboutQuoteUnraveled(...),
            ],
            WorldwideQuoteDrafted::class => [
                static::auditDraftedEvent(...),
            ],
            WorldwideQuoteDeleted::class => [
                static::auditDeletedEvent(...),
            ],
            WorldwideQuoteOwnershipChanged::class => [
                static::auditOwnershipChangedEvent(...),
                static::notifyAboutOwnershipChanged(...),
            ],
            NewVersionOfWorldwideQuoteCreated::class => [
                static::auditNewVersionCreatedEvent(...),
            ],
            WorldwideQuoteVersionDeleted::class => [
                static::auditVersionDeletedEvent(...),
            ],
            WorldwideContractQuoteDetailsStepProcessed::class => [
                static::auditContractQuoteDetailsStepProcessedEvent(...),
            ],
            WorldwideContractQuoteDiscountStepProcessed::class => [
                static::auditContractQuoteDiscountStepProcessedEvent(...),
            ],
            WorldwideContractQuoteImportStepProcessed::class => [
                static::auditContractQuoteImportStepProcessedEvent(...),
            ],
            WorldwideContractQuoteMappingStepProcessed::class => [
                static::auditContractQuoteMappingStepProcessedEvent(...),
            ],
            WorldwideContractQuoteMappingReviewStepProcessed::class => [
                static::auditContractQuoteMappingReviewStepProcessedEvent(...),
            ],
            WorldwidePackQuoteAssetsCreationStepProcessed::class => [
                static::auditPackQuoteAssetsCreationStepProcessedEvent(...),
            ],
            WorldwidePackQuoteAssetsReviewStepProcessed::class => [
                static::auditPackQuoteAssetsReviewStepProcessedEvent(...),
            ],
            WorldwidePackQuoteContactsStepProcessed::class => [
                static::auditPackQuoteContactsStepProcessedEvent(...),
            ],
            WorldwidePackQuoteDetailsStepProcessed::class => [
                static::auditPackQuoteDetailsStepProcessedEvent(...),
            ],
            WorldwidePackQuoteDiscountStepProcessed::class => [
                static::auditPackQuoteDiscountStepProcessedEvent(...),
            ],
            WorldwidePackQuoteMarginStepProcessed::class => [
                static::auditPackQuoteMarginStepProcessedEvent(...),
            ],
            WorldwideQuoteFilesExported::class => [
                static::auditFileExportedEvent(...),
            ],
        ];
    }

    private function getActiveQuoteVersionStage(WorldwideQuote $quote): ?string
    {
        return match ($quote->contractType()->getParentKey()) {
            CT_PACK => PackQuoteStage::getLabelOfValue($quote->activeVersion->completeness),
            CT_CONTRACT => ContractQuoteStage::getLabelOfValue($quote->activeVersion->completeness),
            default => null,
        };
    }

    public function auditVersionDeletedEvent(WorldwideQuoteVersionDeleted $event): void
    {
        $quote = $event->getQuote();
        $version = $event->getQuoteVersion();

        $this->activityLogger
            ->performedOn($quote)
            ->by($event->getActingUser())
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'deleted_version' => sprintf('%s %s', $version->user->user_fullname,
                        $version->user_version_sequence_number),
                ],
            ])
            ->log('deleted_version');
    }

    public function auditNewVersionCreatedEvent(NewVersionOfWorldwideQuoteCreated $event): void
    {
        $newVersion = $event->getNewQuoteVersion();
        $previousVersion = $event->getPreviousQuoteVersion();
        $baseQuote = $newVersion->worldwideQuote;

        $this->activityLogger
            ->performedOn($baseQuote)
            ->by($event->getActingUser())
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [
                    'active_version' => sprintf('%s %s', $previousVersion->user->user_fullname,
                        $previousVersion->user_version_sequence_number),
                ],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'active_version' => sprintf('%s %s', $newVersion->user->user_fullname,
                        $newVersion->user_version_sequence_number),
                ],
            ])
            ->log('created_version');
    }

    public function handleInitializedEvent(WorldwideQuoteInitialized $event): void
    {
        $quote = $event->getQuote();

        $this->activityLogger
            ->performedOn($quote)
            ->by($event->getActingUser())
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'contract_type' => $quote->contractType->type_short_name,
                    'project_name' => $quote->opportunity->project_name,
                    'stage' => $this->getActiveQuoteVersionStage($quote),
                    'quote_number' => $quote->quote_number,
                ],
            ])
            ->log('created');
    }

    public function auditSubmittedEvent(WorldwideQuoteSubmitted $event): void
    {
        [$quote, $oldQuote] = [$event->getQuote(), $event->getOldQuote()];

        $this->activityLogger
            ->performedOn($quote)
            ->by($event->getActingUser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    [
                        'submitted_at' => $oldQuote->submitted_at,
                    ],
                    [
                        'submitted_at' => $quote->submitted_at,
                    ]
                )
            )
            ->log('submitted');
    }

    public function createNoteForSubmittedQuote(WorldwideQuoteSubmitted $event): void
    {
        $quote = $event->getQuote();

        $this->noteEntityService
            ->setCauser($this->appUserResolver->resolve())
            ->createNoteForModel(
                data: new CreateNoteData(
                    note: "<p>Quote $quote->quote_number has been submitted.</p>",
                    flags: Note::SYSTEM,
                ),
                model: $quote->opportunity
            );
    }

    public function createAttachmentFromSubmittedQuote(WorldwideQuoteSubmitted $event): void
    {
        $this->quoteAttachmentService
            ->setCauser($event->getActingUser())
            ->createAttachmentFromSubmittedQuote($event->getQuote());
    }

    public function createAttachmentFromDistributorFiles(WorldwideQuoteSubmitted $event): void
    {
        $this->quoteAttachmentService
            ->setCauser($event->getActingUser())
            ->createAttachmentFromDistributorFiles($event->getQuote());
    }

    public function notifyAboutQuoteSubmitted(WorldwideQuoteSubmitted $event): void
    {
        $quote = $event->getQuote();

        if ($quote->user) {
            $quote->user->notify(new WorldwideQuoteSubmittedNotification($quote));
        }
    }

    public function auditUnraveledEvent(WorldwideQuoteUnraveled $event): void
    {
        $quote = $event->getQuote();

        $this->activityLogger
            ->performedOn($quote)
            ->by($event->getActingUser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    [
                        'submitted_at' => null,
                    ],
                    [
                        'submitted_at' => $quote->submitted_at,
                    ]
                )
            )
            ->log('unravel');
    }

    public function notifyAboutQuoteUnraveled(WorldwideQuoteUnraveled $event): void
    {
        $quote = $event->getQuote();

        if ($quote->user) {
            $quote->user->notify(new WorldwideQuoteUnraveledNotification($quote));
        }
    }

    public function auditDraftedEvent(WorldwideQuoteDrafted $event): void
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

        $this->activityLogger
            ->performedOn($quote)
            ->by($event->getActingUser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    [
                        'stage' => $this->getActiveQuoteVersionStage($oldQuote),
                        'closing_date' => $oldQuote->activeVersion->closing_date,
                        'additional_notes' => $oldQuote->activeVersion->additional_notes,
                    ],
                    [
                        'stage' => $this->getActiveQuoteVersionStage($quote),
                        'closing_date' => $quote->activeVersion->closing_date,
                        'additional_notes' => $quote->activeVersion->additional_notes,
                    ]
                )
            )
            ->log('updated');
    }

    public function createNoteForInitializedQuote(WorldwideQuoteInitialized $event): void
    {
        $quote = $event->getQuote();

        $this->noteEntityService
            ->setCauser($this->appUserResolver->resolve())
            ->createNoteForModel(
                data: new CreateNoteData(
                    note: "<p>Quote $quote->quote_number has been drafted.</p>",
                    flags: Note::SYSTEM,
                ),
                model: $quote->opportunity
            );
    }

    public function auditDeletedEvent(WorldwideQuoteDeleted $event): void
    {
        $quote = $event->getQuote();

        $this->activityLogger
            ->performedOn($quote)
            ->by($event->getActingUser())
            ->log('deleted');
    }

    public function auditOwnershipChangedEvent(WorldwideQuoteOwnershipChanged $event): void
    {
        $getAttributes = static function (WorldwideQuote $quote) {
            return [
                'owner' => $quote->user?->getIdForHumans(),
            ];
        };

        $this->activityLogger
            ->performedOn($event->quote)
            ->by($event->causer)
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    oldAttributeValues: $getAttributes($event->oldQuote),
                    newAttributeValues: $getAttributes($event->quote),
                )
            )
            ->log('updated');
    }

    public function notifyAboutOwnershipChanged(WorldwideQuoteOwnershipChanged $event): void
    {
        $event->quote->user->notify(new WorldwideQuoteOwnershipChangedNotification($event->quote));
    }

    public function auditContractQuoteDetailsStepProcessedEvent(WorldwideContractQuoteDetailsStepProcessed $event
    ): void {
        $this->activityLogger
            ->performedOn($event->getQuote())
            ->by($event->getActingUser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    [
                        'stage' => $this->getActiveQuoteVersionStage($event->getOldQuote()),
                    ],
                    [
                        'stage' => $this->getActiveQuoteVersionStage($event->getQuote()),
                    ]
                )
            )
            ->log('updated');
    }

    public function auditContractQuoteDiscountStepProcessedEvent(WorldwideContractQuoteDiscountStepProcessed $event
    ): void {
        $distributorQuoteDiscountsMapper = function (WorldwideQuoteVersion $quote) {
            $discountsData = [
                'multi_year_discounts' => [],
                'promotional_discounts' => [],
                'pre_pay_discounts' => [],
                'special_negotiation_discounts' => [],
                'custom_discounts' => [],
            ];

            foreach ($quote->worldwideDistributions as $distributorQuote) {
                if (!is_null($distributorQuote->multiYearDiscount)) {
                    $discountsData['multi_year_discounts'][] = sprintf('[%s]: %s',
                        $distributorQuote->opportunitySupplier->supplier_name,
                        $distributorQuote->multiYearDiscount->name);
                }

                if (!is_null($distributorQuote->promotionalDiscount)) {
                    $discountsData['promotional_discounts'][] = sprintf('[%s]: %s',
                        $distributorQuote->opportunitySupplier->supplier_name,
                        $distributorQuote->promotionalDiscount->name);
                }

                if (!is_null($distributorQuote->prePayDiscount)) {
                    $discountsData['pre_pay_discounts'][] = sprintf('[%s]: %s',
                        $distributorQuote->opportunitySupplier->supplier_name, $distributorQuote->prePayDiscount->name);
                }

                if (!is_null($distributorQuote->snDiscount)) {
                    $discountsData['special_negotiation_discounts'][] = sprintf('[%s]: %s',
                        $distributorQuote->opportunitySupplier->supplier_name, $distributorQuote->snDiscount->name);
                }

                if (!is_null($distributorQuote->custom_discount)) {
                    $discountsData['custom_discounts'][] = sprintf('[%s]: %s%%',
                        $distributorQuote->opportunitySupplier->supplier_name,
                        number_format((float) $distributorQuote->custom_discount, 2));
                }
            }

            return array_map(function (array $discounts) {
                return implode("\n", $discounts);
            }, $discountsData);
        };

        $this->activityLogger
            ->performedOn($event->getQuote())
            ->by($event->getActingUser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    [
                        'stage' => $this->getActiveQuoteVersionStage($event->getOldQuote()),
                    ] + $distributorQuoteDiscountsMapper($event->getOldQuote()->activeVersion),
                    [
                        'stage' => $this->getActiveQuoteVersionStage($event->getQuote()),
                    ] + $distributorQuoteDiscountsMapper($event->getQuote()->activeVersion)
                )
            )
            ->log('updated');
    }

    public function auditContractQuoteImportStepProcessedEvent(WorldwideContractQuoteImportStepProcessed $event): void
    {
        $addressToString = function (Address $address) {
            return implode(', ', array_filter([
                $address->address_type, $address->address_1, $address->city, $address->state, $address->post_code,
                optional($address->country)->iso_3166_2,
            ]));
        };

        $contactToString = function (Contact $contact) {
            return implode(', ', array_filter([
                $contact->contact_type, $contact->first_name, $contact->last_name, $contact->email, $contact->phone,
            ]));
        };

        $distributorQuoteSetupDataMapper = function (WorldwideQuoteVersion $quote) use (
            $contactToString,
            $addressToString
        ) {
            $setupData = [
                'vendors' => [],
                'countries' => [],
                'distributor_quote_currencies' => [],
                'buy_quote_currencies' => [],
                'buy_prices' => [],
                'expiry_dates' => [],
                'distributor_files' => [],
                'payment_schedule_files' => [],
                'addresses' => [],
                'contacts' => [],
            ];

            foreach ($quote->worldwideDistributions as $distributorQuote) {
                if ($distributorQuote->vendors->isNotEmpty()) {
                    $setupData['vendors'][] = sprintf('[%s]: %s', $distributorQuote->opportunitySupplier->supplier_name,
                        $distributorQuote->vendors->pluck('short_code')->join(', '));
                }

                if ($distributorQuote->addresses->isNotEmpty()) {
                    $setupData['addresses'][] = sprintf('[%s]: %s',
                        $distributorQuote->opportunitySupplier->supplier_name,
                        $distributorQuote->addresses->map($addressToString)->join('; '));
                }

                if ($distributorQuote->contacts->isNotEmpty()) {
                    $setupData['contacts'][] = sprintf('[%s]: %s',
                        $distributorQuote->opportunitySupplier->supplier_name,
                        $distributorQuote->contacts->map($contactToString)->join('; '));
                }

                if (!is_null($distributorQuote->country)) {
                    $setupData['countries'][] = sprintf('[%s]: %s',
                        $distributorQuote->opportunitySupplier->supplier_name, $distributorQuote->country->iso_3166_2);
                }

                if (!is_null($distributorQuote->distributionCurrency)) {
                    $setupData['distributor_quote_currencies'][] = sprintf('[%s]: %s',
                        $distributorQuote->opportunitySupplier->supplier_name,
                        $distributorQuote->distributionCurrency->code);
                }

                if (!is_null($distributorQuote->buyCurrency)) {
                    $setupData['buy_quote_currencies'][] = sprintf('[%s]: %s',
                        $distributorQuote->opportunitySupplier->supplier_name, $distributorQuote->buyCurrency->code);
                }

                $setupData['buy_prices'][] = sprintf('[%s]: %s', $distributorQuote->opportunitySupplier->supplier_name,
                    number_format((float) $distributorQuote->buy_price, 2));

                $setupData['expiry_dates'][] = sprintf('[%s]: %s',
                    $distributorQuote->opportunitySupplier->supplier_name, $distributorQuote->distribution_expiry_date);

                if (!is_null($distributorQuote->distributorFile)) {
                    $setupData['distributor_files'][] = sprintf('[%s]: %s',
                        $distributorQuote->opportunitySupplier->supplier_name,
                        $distributorQuote->distributorFile->original_file_name);
                }

                if (!is_null($distributorQuote->scheduleFile)) {
                    $setupData['payment_schedule_files'][] = sprintf('[%s]: %s',
                        $distributorQuote->opportunitySupplier->supplier_name,
                        $distributorQuote->scheduleFile->original_file_name);
                }
            }

            return array_map(function (array $discounts) {
                return implode("\n", $discounts);
            }, $setupData);
        };

        $this->activityLogger
            ->performedOn($event->getQuote())
            ->by($event->getActingUser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    [
                        'stage' => $this->getActiveQuoteVersionStage($event->getOldQuote()),
                    ] + $distributorQuoteSetupDataMapper($event->getOldQuote()->activeVersion),
                    [
                        'stage' => $this->getActiveQuoteVersionStage($event->getQuote()),
                    ] + $distributorQuoteSetupDataMapper($event->getQuote()->activeVersion)
                )
            )
            ->log('updated');
    }

    public function auditContractQuoteMappingStepProcessedEvent(WorldwideContractQuoteMappingStepProcessed $event
    ): void {
        $distributorQuoteMappingToString = function (WorldwideDistribution $distributorQuote): string {
            $mappingData = array_map(function (DistributionFieldColumn $field) {
                $value = value(function () use ($field) {
                    $value = '';
                    $properties = [];

                    if (!is_null($field->importable_column_id)) {
                        $value = sprintf("'%s'", $field->importableColumn->header);
                    } else {
                        $value = 'blank';
                    }

                    if ($field->is_default_enabled) {
                        $properties[] = 'default';
                    }

                    if ($field->is_editable) {
                        $properties[] = 'editable';
                    }

                    return sprintf('%s [%s]', $value, implode(', ', $properties));
                });

                return "$field->template_field_name -> $value";
            }, $distributorQuote->mapping->all());

            return implode('; ', $mappingData);
        };

        $distributorQuoteMappingMapper = function (WorldwideQuoteVersion $version) use ($distributorQuoteMappingToString
        ) {
            $mappingData = [];

            foreach ($version->worldwideDistributions as $distributorQuote) {
                $mappingData[] = sprintf('[%s]: %s', $distributorQuote->opportunitySupplier->supplier_name,
                    $distributorQuoteMappingToString($distributorQuote));
            }

            return implode("\n", $mappingData);
        };

        $this->activityLogger
            ->performedOn($event->getQuote())
            ->by($event->getActingUser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    [
                        'stage' => $this->getActiveQuoteVersionStage($event->getOldQuote()),
                        'mapping' => $distributorQuoteMappingMapper($event->getOldQuote()->activeVersion),
                    ],
                    [
                        'stage' => $this->getActiveQuoteVersionStage($event->getQuote()),
                        'mapping' => $distributorQuoteMappingMapper($event->getQuote()->activeVersion),
                    ]
                )
            )
            ->log('updated');
    }

    public function auditContractQuoteMappingReviewStepProcessedEvent(
        WorldwideContractQuoteMappingReviewStepProcessed $event
    ): void {
        $this->activityLogger
            ->performedOn($event->getQuote())
            ->by($event->getActingUser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    [
                        'stage' => $this->getActiveQuoteVersionStage($event->getOldQuote()),
                    ],
                    [
                        'stage' => $this->getActiveQuoteVersionStage($event->getQuote()),
                    ]
                )
            )
            ->log('updated');
    }

    public function auditPackQuoteAssetsCreationStepProcessedEvent(WorldwidePackQuoteAssetsCreationStepProcessed $event
    ): void {
        $this->activityLogger
            ->performedOn($event->getQuote())
            ->by($event->getActingUser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    [
                        'stage' => $this->getActiveQuoteVersionStage($event->getOldQuote()),
                    ],
                    [
                        'stage' => $this->getActiveQuoteVersionStage($event->getQuote()),
                    ]
                )
            )
            ->log('updated');
    }

    public function auditPackQuoteAssetsReviewStepProcessedEvent(WorldwidePackQuoteAssetsReviewStepProcessed $event
    ): void {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

        $this->activityLogger
            ->performedOn($event->getQuote())
            ->by($event->getActingUser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    [
                        'stage' => $this->getActiveQuoteVersionStage($oldQuote),
                        'sort_rows_column' => $oldQuote->activeVersion->sort_rows_column,
                        'sort_rows_direction' => $oldQuote->activeVersion->sort_rows_direction,
                        'selected_rows_count' => $oldQuote->activeVersion->selected_rows_count,
                    ],
                    [
                        'stage' => $this->getActiveQuoteVersionStage($quote),
                        'sort_rows_column' => $quote->activeVersion->sort_rows_column,
                        'sort_rows_direction' => $quote->activeVersion->sort_rows_direction,
                        'selected_rows_count' => $quote->activeVersion->selected_rows_count,
                    ]
                )
            )
            ->log('updated');
    }

    public function auditPackQuoteContactsStepProcessedEvent(WorldwidePackQuoteContactsStepProcessed $event): void
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

        $this->activityLogger
            ->performedOn($event->getQuote())
            ->by($event->getActingUser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    [
                        'stage' => $this->getActiveQuoteVersionStage($oldQuote),
                        'company_name' => transform($oldQuote->activeVersion->company,
                            fn (Company $company) => $company->name),
                        'quote_currency' => transform($oldQuote->activeVersion->quoteCurrency,
                            fn (Currency $currency) => $currency->code),
                        'buy_currency' => transform($oldQuote->activeVersion->buyCurrency,
                            fn (Currency $currency) => $currency->code),
                        'quote_template' => transform($oldQuote->activeVersion->quoteTemplate,
                            fn (QuoteTemplate $template) => $template->name),
                        'buy_price' => $oldQuote->activeVersion->buy_price,
                        'quote_expiry_date' => $oldQuote->activeVersion->quote_expiry_date,
                        'payment_terms' => $oldQuote->activeVersion->payment_terms,
                    ],
                    [
                        'stage' => $this->getActiveQuoteVersionStage($quote),
                        'company_name' => transform($quote->activeVersion->company,
                            fn (Company $company) => $company->name),
                        'quote_currency' => transform($quote->activeVersion->quoteCurrency,
                            fn (Currency $currency) => $currency->code),
                        'buy_currency' => transform($quote->activeVersion->buyCurrency,
                            fn (Currency $currency) => $currency->code),
                        'quote_template' => transform($quote->activeVersion->quoteTemplate,
                            fn (QuoteTemplate $template) => $template->name),
                        'buy_price' => $quote->activeVersion->buy_price,
                        'quote_expiry_date' => $quote->activeVersion->quote_expiry_date,
                        'payment_terms' => $quote->activeVersion->payment_terms,
                    ]
                )
            )
            ->log('updated');
    }

    public function auditPackQuoteDetailsStepProcessedEvent(WorldwidePackQuoteDetailsStepProcessed $event): void
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

        $this->activityLogger
            ->performedOn($event->getQuote())
            ->by($event->getActingUser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    [
                        'stage' => $this->getActiveQuoteVersionStage($oldQuote),
                        'pricing_document' => $oldQuote->activeVersion->pricing_document,
                        'service_agreement_id' => $oldQuote->activeVersion->service_agreement_id,
                        'system_handle' => $oldQuote->activeVersion->system_handle,
                        'additional_details' => $oldQuote->activeVersion->additional_details,
                    ],
                    [
                        'stage' => $this->getActiveQuoteVersionStage($quote),
                        'pricing_document' => $quote->activeVersion->pricing_document,
                        'service_agreement_id' => $quote->activeVersion->service_agreement_id,
                        'system_handle' => $quote->activeVersion->system_handle,
                        'additional_details' => $quote->activeVersion->additional_details,
                    ]
                )
            )
            ->log('updated');
    }

    public function auditPackQuoteDiscountStepProcessedEvent(WorldwidePackQuoteDiscountStepProcessed $event): void
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

        $this->activityLogger
            ->performedOn($event->getQuote())
            ->by($event->getActingUser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    [
                        'stage' => $this->getActiveQuoteVersionStage($oldQuote),
                        'multi_year_discount' => transform($oldQuote->activeVersion->multiYearDiscount,
                            fn (MultiYearDiscount $discount) => $discount->name),
                        'pre_pay_discount' => transform($oldQuote->activeVersion->prePayDiscount,
                            fn (PrePayDiscount $discount) => $discount->name),
                        'promotional_discount' => transform($oldQuote->activeVersion->promotionalDiscount,
                            fn (PromotionalDiscount $discount) => $discount->name),
                        'sn_discount' => transform($oldQuote->activeVersion->snDiscount,
                            fn (SND $discount) => $discount->name),
                        'custom_discount' => number_format((float) $oldQuote->activeVersion->custom_discount),
                    ],
                    [
                        'stage' => $this->getActiveQuoteVersionStage($quote),
                        'multi_year_discount' => transform($quote->activeVersion->multiYearDiscount,
                            fn (MultiYearDiscount $discount) => $discount->name),
                        'pre_pay_discount' => transform($quote->activeVersion->prePayDiscount,
                            fn (PrePayDiscount $discount) => $discount->name),
                        'promotional_discount' => transform($quote->activeVersion->promotionalDiscount,
                            fn (PromotionalDiscount $discount) => $discount->name),
                        'sn_discount' => transform($quote->activeVersion->snDiscount,
                            fn (SND $discount) => $discount->name),
                        'custom_discount' => number_format((float) $quote->activeVersion->custom_discount),
                    ]
                )
            )
            ->log('updated');
    }

    public function auditPackQuoteMarginStepProcessedEvent(WorldwidePackQuoteMarginStepProcessed $event): void
    {
        $quote = $event->getQuote();
        $oldQuote = $event->getOldQuote();

        $this->activityLogger
            ->performedOn($event->getQuote())
            ->by($event->getActingUser())
            ->withProperties(
                $this->changesDetector->diffAttributeValues(
                    [
                        'stage' => $this->getActiveQuoteVersionStage($oldQuote),
                        'quote_type' => $oldQuote->activeVersion->quote_type,
                        'margin_value' => $oldQuote->activeVersion->margin_value,
                        'margin_method' => $oldQuote->activeVersion->margin_method,
                        'tax_value' => $oldQuote->activeVersion->tax_value,
                    ],
                    [
                        'stage' => $this->getActiveQuoteVersionStage($quote),
                        'quote_type' => $quote->activeVersion->quote_type,
                        'margin_value' => $quote->activeVersion->margin_value,
                        'margin_method' => $quote->activeVersion->margin_method,
                        'tax_value' => $quote->activeVersion->tax_value,
                    ]
                )
            )
            ->log('updated');
    }

    public function auditFileExportedEvent(WorldwideQuoteFilesExported $event): void
    {
        $quote = $event->getQuote();
        $quoteFiles = $event->getExportedFiles();

        if ($quoteFiles->isEmpty()) {
            return;
        }

        $typeOfFiles = $quoteFiles->first()->file_type;

        $this->activityLogger
            ->on($quote)
            ->withProperties([
                ChangesDetector::OLD_ATTRS_KEY => [],
                ChangesDetector::NEW_ATTRS_KEY => [
                    'exported_files' => sprintf('%s: %s',
                        Str::plural($typeOfFiles),
                        $quoteFiles->map(fn (QuoteFile $file) => "`$file->original_file_name`")->implode(', ')),
                ],
            ])
            ->log('exported');
    }
}
