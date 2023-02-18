<?php

namespace App\Domain\Worldwide\Models;

use App\Domain\Address\Models\Address;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Currency\Models\Currency;
use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Discount\Models\SND;
use App\Domain\Note\Models\ModelHasNotes;
use App\Domain\Note\Models\Note;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\User\Models\User;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Database\Factories\WorldwideQuoteVersionFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class WorldwideQuoteVersion.
 * /**
 * @property string|null                                                                              $id
 * @property string|null                                                                              $worldwide_quote_id
 * @property string|null                                                                              $user_id
 * @property string|null                                                                              $company_id
 * @property string|null                                                                              $quote_currency_id
 * @property string|null                                                                              $buy_currency_id
 * @property string|null                                                                              $output_currency_id
 * @property string|null                                                                              $quote_template_id
 * @property float|null                                                                               $exchange_rate_margin
 * @property int|null                                                                                 $completeness
 * @property string|null                                                                              $quote_expiry_date
 * @property string|null                                                                              $closing_date
 * @property string|null                                                                              $assets_migrated_at
 * @property float|null                                                                               $buy_price
 * @property float|null                                                                               $actual_exchange_rate_value
 * @property string|null                                                                              $additional_notes
 * @property int|null                                                                                 $user_version_sequence_number
 * @property string|null                                                                              $quote_type
 * @property float|null                                                                               $tax_value
 * @property float|null                                                                               $margin_value
 * @property string|null                                                                              $margin_method
 * @property float|null                                                                               $custom_discount
 * @property string|null                                                                              $payment_terms
 * @property string|null                                                                              $pricing_document
 * @property string|null                                                                              $service_agreement_id
 * @property string|null                                                                              $system_handle
 * @property string|null                                                                              $additional_details
 * @property string|null                                                                              $sort_rows_column
 * @property string|null                                                                              $sort_rows_direction
 * @property string|null                                                                              $sort_assets_groups_column
 * @property string|null                                                                              $sort_assets_groups_direction
 * @property bool|null                                                                                $use_groups
 * @property bool|null                                                                                $are_end_user_addresses_available
 * @property bool|null                                                                                $are_end_user_contacts_available
 * @property float|null                                                                               $total_price
 * @property float|null                                                                               $margin_percentage
 * @property float|null                                                                               $final_total_price
 * @property float|null                                                                               $final_total_price_excluding_tax
 * @property float|null                                                                               $applicable_discounts_value
 * @property float|null                                                                               $final_margin
 * @property User                                                                                     $user
 * @property \App\Domain\ContractType\Models\ContractType|null                                        $contractType
 * @property Collection<WorldwideDistribution>|WorldwideDistribution[]                                $worldwideDistributions
 * @property \App\Domain\Rescue\Models\QuoteTemplate|null                                             $quoteTemplate
 * @property \App\Domain\Currency\Models\Currency                                                     $quoteCurrency
 * @property \App\Domain\Currency\Models\Currency                                                     $buyCurrency
 * @property \App\Domain\Currency\Models\Currency                                                     $outputCurrency
 * @property Company|null                                                                             $company
 * @property Collection<WorldwideQuoteAsset>|WorldwideQuoteAsset[]                                    $assets
 * @property MultiYearDiscount|null                                                                   $multiYearDiscount
 * @property \App\Domain\Discount\Models\PrePayDiscount|null                                          $prePayDiscount
 * @property PromotionalDiscount|null                                                                 $promotionalDiscount
 * @property SND|null                                                                                 $snDiscount
 * @property Collection|null                                                                          $applicableSnDiscounts
 * @property Collection|null                                                                          $applicablePromotionalDiscounts
 * @property Collection|null                                                                          $applicablePrePayDiscounts
 * @property Collection|null                                                                          $applicableMultiYearDiscounts
 * @property WorldwideQuote|null                                                                      $worldwideQuote
 * @property Collection<int, \App\Domain\Address\Models\Address>|\App\Domain\Address\Models\Address[] $addresses
 * @property Collection<int, Contact>|Contact[]                                                       $contacts
 * @property Collection<int, Note>|Note[]                                                             $notes
 * @property Note|null                                                                                $note
 * @property Note|null                                                                                $draftNote
 * @property \App\Domain\Note\Models\Note|null                                                        $submitNote
 * @property Collection<WorldwideQuoteAssetsGroup>|WorldwideQuoteAssetsGroup[]                        $assetsGroups
 * @property \DateTimeInterface|null                                                                  $created_at
 */
class WorldwideQuoteVersion extends Model
{
    use Uuid;
    use SoftDeletes;
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): WorldwideQuoteVersionFactory
    {
        return WorldwideQuoteVersionFactory::new();
    }

    public function worldwideQuote(): BelongsTo
    {
        return $this->belongsTo(WorldwideQuote::class);
    }

    public function assets(): MorphMany
    {
        return tap($this->morphMany(WorldwideQuoteAsset::class, 'worldwide_quote'), function (MorphMany $relation) {
            $relation->addSelect([
                'vendor_short_code' => \App\Domain\Vendor\Models\Vendor::query()->select('short_code')
                    ->from('vendors')
                    ->whereColumn('vendors.id', 'worldwide_quote_assets.vendor_id')->limit(1)->toBase(),
            ])
                ->oldest($relation->getRelated()->getQualifiedCreatedAtColumn())
                ->oldest('entity_order');
        });
    }

    public function assetsGroups(): HasMany
    {
        return tap($this->hasMany(WorldwideQuoteAssetsGroup::class), function (HasMany $relation) {
            $relation
                ->withCount('assets')
                ->withSum('assets', 'price')
                ->withCasts(['rows_sum' => 'float'])
                ->oldest($relation->getRelated()->getQualifiedCreatedAtColumn());
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quoteCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class)->withDefault();
    }

    public function buyCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class)->withDefault();
    }

    public function outputCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class)->withDefault();
    }

    public function quoteTemplate(): BelongsTo
    {
        return $this->belongsTo(QuoteTemplate::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Company\Models\Company::class);
    }

    public function worldwideDistributions(): MorphMany
    {
        return $this->morphMany(WorldwideDistribution::class, 'worldwide_quote')
            // the clause is required to sync Worldwide Distribution with Opportunity Supplier Entities
            ->has('opportunitySupplier');
    }

    public function multiYearDiscount(): BelongsTo
    {
        return $this->belongsTo(MultiYearDiscount::class);
    }

    public function prePayDiscount(): BelongsTo
    {
        return $this->belongsTo(PrePayDiscount::class);
    }

    public function promotionalDiscount(): BelongsTo
    {
        return $this->belongsTo(PromotionalDiscount::class);
    }

    public function snDiscount(): BelongsTo
    {
        return $this->belongsTo(SND::class);
    }

    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(Address::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class);
    }

    public function notes(): MorphToMany
    {
        return $this->morphToMany(
            related: Note::class,
            name: 'model',
            table: (new ModelHasNotes())->getTable(),
            relatedPivotKey: 'note_id',
        )->using(ModelHasNotes::class);
    }

    protected function note(): Attribute
    {
        return Attribute::get(function () {
            return $this->notes->first();
        });
    }

    protected function draftNote(): Attribute
    {
        return Attribute::get(function () {
            return $this->notes
                ->first(static fn (Note $note): bool => $note->getFlag(Note::FROM_ENTITY_WIZARD_DRAFT));
        });
    }

    protected function submitNote(): Attribute
    {
        return Attribute::get(function () {
            return $this->notes
                ->first(static fn (Note $note): bool => $note->getFlag(Note::FROM_ENTITY_WIZARD_SUBMIT));
        });
    }
}
