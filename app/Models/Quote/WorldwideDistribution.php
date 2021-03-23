<?php

namespace App\Models\Quote;

use App\Models\Address;
use App\Models\Contact;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use App\Models\OpportunitySupplier;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Models\Quote\Margin\CountryMargin;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\ImportedRow;
use App\Models\QuoteFile\MappedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Models\Template\TemplateField;
use App\Models\Vendor;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;
use Staudenmeir\EloquentHasManyDeep\HasOneDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * @property string|null $worldwide_quote_id
 * @property string|null $worldwide_quote_type
 * @property string|null $opportunity_supplier_id
 * @property string|null $distributor_file_id
 * @property string|null $schedule_file_id
 * @property string|null $company_id
 * @property string|null $country_id
 * @property string|null $distribution_currency_id
 * @property mixed $multi_year_discount_id
 * @property mixed $pre_pay_discount_id
 * @property mixed $promotional_discount_id
 * @property mixed $sn_discount_id
 * @property string $country_margin_id
 * @property float|null $custom_discount
 * @property float|null $tax_value
 * @property string|null $margin_value
 * @property string $buy_price
 * @property bool $calculate_list_price
 * @property bool $use_groups
 * @property string|null $sort_rows_column
 * @property string|null $sort_rows_direction
 * @property string|null $sort_rows_groups_column
 * @property string|null $sort_rows_groups_direction
 * @property string $pricing_document
 * @property string $service_agreement_id
 * @property string $system_handle
 * @property string $additional_details
 * @property string $additional_notes
 * @property string|null $purchase_order_number
 * @property string|null $vat_number
 * @property array|null $checkbox_status
 * @property string $distribution_expiry_date
 * @property mixed $quote_type
 * @property mixed $margin_method
 * @property WorldwideQuote $worldwideQuote
 * @property Currency|null $distributionCurrency
 * @property QuoteFile|null $distributorFile
 * @property QuoteFile|null $scheduleFile
 * @property Collection<Vendor> $vendors
 * @property Collection<MappedRow> $mappedRows
 * @property Collection<DistributionRowsGroup> $rowsGroups
 * @property Collection<DistributionFieldColumn> $mapping
 * @property MultiYearDiscount|null $multiYearDiscount
 * @property PromotionalDiscount|null $promotionalDiscount
 * @property PrePayDiscount|null $prePayDiscount
 * @property SND|null $snDiscount
 * @property Collection|null $applicableSnDiscounts
 * @property Collection|null $applicablePromotionalDiscounts
 * @property Collection|null $applicablePrePayDiscounts
 * @property Collection|null $applicableMultiYearDiscounts
 * @property float|null $final_total_price
 * @property float|null $final_total_price_excluding_tax
 * @property float|null $total_price
 * @property float|null $margin_percentage
 * @property float|null $margin_percentage_after_custom_discount
 * @property float|null $final_margin
 * @property float|null $distribution_exchange_rate
 * @property Country|null $country
 * @property float|null $applicable_discounts_value
 * @property string|null $imported_at
 *
 * @property Collection<Address>|Address[] $addresses
 * @property Collection<Contact>|Contact[] $contacts
 * @property OpportunitySupplier|null $opportunitySupplier
 * @property ImportedRow|null $mappingRow
 * @property Collection<TemplateField>|TemplateField[] $templateFields
 */
class WorldwideDistribution extends Model
{
    use Uuid, SoftDeletes, HasRelationships;

    protected $guarded = [];

    protected $casts = [
        'checkbox_status' => 'array',
        'margin_value' => 'decimal:2',
    ];

//    protected $hidden = [
//        'worldwide_quote_type'
//    ];

    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(Address::class)->withPivot('is_default');
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class)->withPivot('is_default');
    }

    public function templateFields(): BelongsToMany
    {
        return $this->belongsToMany(TemplateField::class, (new DistributionFieldColumn)->getTable());
    }

    public function worldwideQuote(): MorphTo
    {
        return $this->morphTo();
    }

    public function opportunitySupplier(): BelongsTo
    {
        return $this->belongsTo(OpportunitySupplier::class);
    }

    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function distributionCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function distributorFile(): BelongsTo
    {
        return $this->belongsTo(QuoteFile::class);
    }

    public function scheduleFile(): BelongsTo
    {
        return $this->belongsTo(QuoteFile::class);
    }

    public function countryMargin(): BelongsTo
    {
        return $this->belongsTo(CountryMargin::class);
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

    public function mapping(): HasMany
    {
        $mappingModel = (new DistributionFieldColumn);
        $fieldModel = (new TemplateField);

        return $this->hasMany(DistributionFieldColumn::class)
            ->join($fieldModel->getTable(), function (JoinClause $join) use ($mappingModel, $fieldModel) {
                $join->on($fieldModel->getQualifiedKeyName(), $mappingModel->qualifyColumn($fieldModel->getForeignKey()));
            })
            ->select(
                $mappingModel->qualifyColumn('*'),
                "{$fieldModel->qualifyColumn('name')} as template_field_name",
                "{$fieldModel->qualifyColumn('header')} as template_field_header"
//                $fieldModel->qualifyColumn('is_required')
            )
            ->orderBy($fieldModel->qualifyColumn('order'))
            ->withCasts(['is_required' => 'boolean']);
    }

    public function mappingRow(): HasOneDeep
    {
        $fileModel = (new QuoteFile);
        $rowsRelation = $fileModel->rowsData();

        return $this->hasOneDeepFromRelations($this->distributorFile(), $rowsRelation)
            ->whereColumn(
                $rowsRelation->getRelated()->qualifyColumn('page'),
                '>=',
                $fileModel->qualifyColumn('imported_page')
            );
    }

    public function mappedRows(): HasManyThrough
    {
        return $this->hasManyDeepFromRelations($this->distributorFile(), $rowsRelation = (new QuoteFile)->mappedRows());
    }

    public function rowsGroups(): HasMany
    {
        return tap($this->hasMany(DistributionRowsGroup::class), function (HasMany $relation) {
            $rowModel = (new MappedRow);
            /** @var DistributionRowsGroup */
            $groupModel = $relation->getRelated();
            $rowsRelation = $groupModel->rows();

            $relation
                ->withCount('rows')
                ->addSelect([
                    'rows_sum' => $rowModel->selectRaw('sum(price)')
                        ->join($rowsRelation->getTable(), function (JoinClause $join) use ($rowsRelation, $groupModel) {
                            $join->on($rowsRelation->getRelated()->getQualifiedKeyName(), $rowsRelation->getQualifiedRelatedPivotKeyName());
                        })
                        ->whereColumn($groupModel->getQualifiedKeyName(), $rowsRelation->getQualifiedForeignPivotKeyName()),
                ])
                ->withCasts(['rows_sum' => 'float']);
//                ->withCasts(['rows_sum' => 'decimal:2']);
        });
    }
}
