<?php

namespace App\Domain\Rescue\Models;

use App\Domain\Activity\Concerns\LogsActivity;
use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\Company\Concerns\BelongsToCompany;
use App\Domain\Country\Concerns\BelongsToCountry;
use App\Domain\Currency\Models\Currency;
use App\Domain\Discount\Concerns\HasMorphableDiscounts;
use App\Domain\Eloquent\Contracts\HasOrderedScope;
use App\Domain\Note\Contracts\HasOwnNotes;
use App\Domain\Note\Models\Note;
use App\Domain\QuoteFile\Models\ImportedRow;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\QuoteFile\Models\ScheduleData;
use App\Domain\Rescue\Casts\GroupDescription;
use App\Domain\Rescue\Concerns\BelongsToCustomer;
use App\Domain\Rescue\Concerns\BelongsToMargin;
use App\Domain\Rescue\Concerns\ConvertsCurrency;
use App\Domain\Rescue\Concerns\HasMarginPercentageAttribute;
use App\Domain\Rescue\Concerns\Reviewable;
use App\Domain\Rescue\Concerns\SavesPreviousState;
use App\Domain\Rescue\Queries\QuoteQueries;
use App\Domain\Rescue\Quote\HasAdditionalHtmlAttributes;
use App\Domain\Rescue\Quote\HasCustomDiscountAttribute;
use App\Domain\Rescue\Quote\HasGroupDescriptionAttribute;
use App\Domain\Rescue\Quote\HasMapping;
use App\Domain\Rescue\Quote\SwitchesMode;
use App\Domain\Shared\Eloquent\Concerns\Searchable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Shared\Eloquent\Contracts\ActivatableInterface;
use App\Domain\Template\Concerns\BelongsToContractTemplate;
use App\Domain\Template\Concerns\BelongsToQuoteTemplate;
use App\Domain\Template\Models\TemplateField;
use App\Domain\User\Concerns\BelongsToUser;
use App\Domain\Vendor\Concerns\BelongsToVendor;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Tappable;

/**
 * @property string|null                                                           $user_id
 * @property \Illuminate\Support\Collection                                        $group_description
 * @property string|null                                                           $country_margin_id
 * @property string|null                                                           $vendor_id
 * @property string|null                                                           $country_id
 * @property float|null                                                            $buy_price
 * @property float|null                                                            $custom_discount
 * @property Collection<TemplateField>|\App\Domain\Template\Models\TemplateField[] $templateFields
 * @property \App\Domain\Margin\Models\CountryMargin|null                          $countryMargin
 * @property Currency                                                              $sourceCurrency
 * @property Currency                                                              $targetCurrency
 * @property float|null                                                            $exchange_rate_margin
 * @property float|null                                                            $target_exchange_rate
 * @property \App\Domain\Vendor\Models\Vendor|null                                 $vendor
 * @property QuoteFile                                                             $priceList
 * @property Note|null                                                             $note
 * @property Customer                                                              $customer
 */
abstract class BaseQuote extends Model implements HasOrderedScope, ActivatableInterface, SearchableEntity, HasOwnNotes
{
    use Uuid;
    use Multitenantable;
    use Searchable;
    use BelongsToUser;
    use BelongsToCustomer;
    use BelongsToCompany;
    use BelongsToVendor;
    use BelongsToCountry;
    use BelongsToMargin;
    use BelongsToQuoteTemplate;
    use BelongsToContractTemplate;
    use SoftDeletes;
    use HasMorphableDiscounts;
    use HasMarginPercentageAttribute;
//        HasPricesAttributes,
    use HasMapping;
    use HasCustomDiscountAttribute;
    use HasGroupDescriptionAttribute;
    use LogsActivity;
    use HasAdditionalHtmlAttributes;
    use Reviewable;
    use SwitchesMode;
    use ConvertsCurrency;
    use SavesPreviousState;
    use Tappable;

    const PRICE_ATTRIBUTES_MAPPING = [
        'pricing_document' => 'pricing_document',
        'system_handle' => 'system_handle',
        'service_agreement_id' => 'searchable',
    ];

    const TYPES = ['New', 'Renewal'];

    protected static $logAttributes = [
        'type',
        'customer.name',
        'company.name',
        'vendor.name',
        'country.name',
        'countryMargin.formatted_value',
        'quoteTemplate.name',
        'last_drafted_step',
        'pricing_document',
        'service_agreement_id',
        'system_handle',
        'closing_date',
        'calculate_list_price',
        'custom_discount',
        'use_groups',
        'buy_price',
        'sourceCurrency.code',
        'targetCurrency.code',
        'exchange_rate_margin',
    ];

    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;
    protected static $saveStateAttributes = ['service_agreement_id'];

    public float $applicableDiscounts = 0.0;
    public float $totalPrice = 0.0;
    public float $totalPriceAfterMargin = 0.0;
    public float $finalTotalPrice = 0.0;
    public float $priceCoef = 1.0;

    protected $fillable = [
        'customer_id',
        'eq_customer_id',
        'distributor_file_id',
        'schedule_file_id',
        'company_id',
        'vendor_id',
        'country_id',
        'last_drafted_step',
        'completeness',
        'quote_template_id',
        'contract_template_id',
        'pricing_document',
        'service_agreement_id',
        'system_handle',
        'checkbox_status',
        'closing_date',
        'calculate_list_price',
        'buy_price',
    ];

    protected $attributes = [
        'completeness' => 1,
        'calculate_list_price' => false,
    ];

    protected $appends = [
        'last_drafted_step',
    ];

    protected $casts = [
        'group_description' => GroupDescription::class,
        'checkbox_status' => 'json',
        'calculate_list_price' => 'boolean',
        'buy_price' => 'float',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected $table = 'quotes';

    protected $dates = ['closing_date'];

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeRfq($query, ?string $rfq)
    {
        return $query->whereHas('customer', fn (Builder $query) => $query->whereRfq($rfq));
    }

    public function scheduleData()
    {
        return $this->hasOneThrough(ScheduleData::class, QuoteFile::class, 'id', null, 'schedule_file_id')
            ->withDefault();
    }

    public function priceList()
    {
        return $this->belongsTo(QuoteFile::class, 'distributor_file_id', 'id')->withDefault();
    }

    public function paymentSchedule()
    {
        return $this->belongsTo(QuoteFile::class, 'schedule_file_id', 'id')->withDefault();
    }

    public function firstRow()
    {
        return $this->rowsData()->limit(1)->oldest();
    }

    public function rowsData()
    {
        return $this->hasManyThrough(ImportedRow::class, QuoteFile::class, 'id', null, 'distributor_file_id')
            ->whereColumn('imported_rows.page', '>=', 'quote_files.imported_page');
    }

    public function toSearchArray(): array
    {
        return [
            'company_name' => optional($this->company)->name,

            'customer_name' => $this->customer->name,
            'customer_rfq' => $this->customer->rfq,
            'customer_valid_until' => $this->customer->valid_until?->toDateString(),
            'customer_support_start' => $this->customer->support_start?->toDateString(),
            'customer_support_end' => $this->customer->support_end?->toDateString(),
            'customer_source' => $this->customer->source,

            'user_fullname' => optional($this->user)->fullname,
            'created_at' => optional($this->created_at)->format(config('date.format')),
        ];
    }

    public function getCompletenessDictionary()
    {
        return __('quote.stages');
    }

    public function getItemNameAttribute()
    {
        return "Quote ({$this->customer->rfq})";
    }

    public function getCurrencySymbolAttribute()
    {
        return $this->targetCurrency->symbol
            ?? $this->sourceCurrency->symbol
            ?? $this->quoteTemplate->currency->symbol
            ?? null;
    }

    public function getForeignKey()
    {
        return Str::snake(Str::after(class_basename(self::class), 'Base')).'_'.$this->getKeyName();
    }

    public function getVersionNameAttribute(): string
    {
        $userName = $this->user_fullname ?? transform($this->user, function () {
            return "{$this->user->first_name} {$this->user->last_name}";
        }, '[USER DELETED]');

        $versionNumber = $this->version_number ?? 1;

        return "{$userName} {$versionNumber}";
    }

    public function getBuyPriceAttribute($value): float
    {
        return $this->convertExchangeRate((float) $value);
    }

    public function getTotalPriceAttribute(): float
    {
        return $this->totalPrice ??= $this->totalPrice = (new QuoteQueries())
            ->mappedSelectedRowsQuery($this)
            ->sum('price');
    }

    public function transformDraftedStep($completeness)
    {
        $dictionary = $this->getCompletenessDictionary();
        $stage = collect($dictionary)->search($completeness, true);

        return $stage;
    }

    public function getLastDraftedStepAttribute()
    {
        return $this->transformDraftedStep($this->completeness);
    }

    public function setLastDraftedStepAttribute(string $value): void
    {
        $dictionary = $this->getCompletenessDictionary();
        $completeness = collect($dictionary)->get($value) ?? $this->completeness;

        $this->setAttribute('completeness', $completeness);
    }

    public static function modelCompleteness()
    {
        return (new static())->getCompletenessDictionary();
    }
}
