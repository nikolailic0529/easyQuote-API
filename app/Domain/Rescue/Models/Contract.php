<?php

namespace App\Domain\Rescue\Models;

use App\Domain\Activity\Concerns\LogsActivity;
use App\Domain\Authentication\Concerns\Multitenantable;
use App\Domain\Company\Concerns\BelongsToCompany;
use App\Domain\Country\Concerns\BelongsToCountry;
use App\Domain\Notification\Concerns\NotifiableModel;
use App\Domain\QuoteFile\Models\ImportableColumn;
use App\Domain\QuoteFile\Models\ImportedRow;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\QuoteFile\Models\ScheduleData;
use App\Domain\Rescue\Casts\GroupDescription;
use App\Domain\Rescue\Concerns\BelongsToCustomer;
use App\Domain\Rescue\Concerns\BelongsToQuote;
use App\Domain\Rescue\Concerns\Reviewable;
use App\Domain\Rescue\Concerns\SavesPreviousState;
use App\Domain\Rescue\Quote\HasAdditionalHtmlAttributes;
use App\Domain\Rescue\Quote\HasGroupDescriptionAttribute;
use App\Domain\Rescue\Quote\SwitchesMode;
use App\Domain\Shared\Eloquent\Concerns\Activatable;
use App\Domain\Shared\Eloquent\Concerns\Searchable;
use App\Domain\Shared\Eloquent\Concerns\Submittable;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Template\Concerns\BelongsToContractTemplate;
use App\Domain\Template\Models\TemplateField;
use App\Domain\User\Concerns\BelongsToUser;
use App\Domain\User\Models\User;
use App\Domain\Vendor\Concerns\BelongsToVendor;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Traits\Tappable;

/**
 * @property Quote|null $quote
 * @property User $user
 * @property string $contract_number
 */
class Contract extends Model implements SearchableEntity
{
    use Uuid;
    use Multitenantable;
    use Searchable;
    use BelongsToUser;
    use BelongsToCustomer;
    use BelongsToCompany;
    use BelongsToVendor;
    use BelongsToCountry;
    use BelongsToContractTemplate;
    use Submittable;
    use Activatable;
    use SoftDeletes;
    use HasGroupDescriptionAttribute;
    use LogsActivity;
    use HasAdditionalHtmlAttributes;
    use Reviewable;
    use SwitchesMode;
    use SavesPreviousState;
    use Tappable;
    use BelongsToQuote;
    use NotifiableModel;

    protected $fillable = [
        'customer_id',
        'distributor_file_id',
        'schedule_file_id',
        'company_id',
        'vendor_id',
        'country_id',
        'last_drafted_step',
        'completeness',
        'contract_template_id',
        'pricing_document',
        'service_agreement_id',
        'customer_name',
        'contract_number',
        'system_handle',
        'contract_date',
    ];

    protected $casts = [
        'group_description' => GroupDescription::class,
    ];

    public function toSearchArray(): array
    {
        $this->loadMissing(
            'customer:id,rfq,valid_until,name,support_start,support_end,valid_until',
            'company:id,name',
            'user:id,first_name,last_name'
        );

        return [
            'company_name' => $this->company->name,
            'contract_number' => $this->contract_number,
            'customer_name' => $this->customer->name,
            'customer_rfq' => $this->customer->rfq,
            'customer_valid_until' => $this->customer->valid_until,
            'customer_support_start' => $this->customer->support_start,
            'customer_support_end' => $this->customer->support_end,
            'user_fullname' => optional($this->user)->fullname,
            'created_at' => $this->created_at,
            'is_submitted' => !is_null($this->submitted_at),
        ];
    }

    public function templateFields(): BelongsToMany
    {
        return $this->belongsToMany(TemplateField::class, 'contract_field_column', 'contract_id');
    }

    public function getSortFieldsAttribute(): BaseCollection
    {
        return $this->fieldsColumns->whereNotNull('sort')->map(
            fn ($column) => ['name' => $column->templateField->name, 'direction' => $column->sort]
        )->values();
    }

    public function importableColumns(): BelongsToMany
    {
        return $this->belongsToMany(ImportableColumn::class, 'contract_field_column', $this->getForeignKey());
    }

    public function fieldsColumns(): HasMany
    {
        return $this->hasMany(ContractFieldColumn::class)->with('templateField');
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

    public function rowsData()
    {
        return $this->hasManyThrough(ImportedRow::class, QuoteFile::class, 'id', null, 'distributor_file_id')
            ->whereColumn('imported_rows.page', '>=', 'quote_files.imported_page');
    }

    public function getItemNameAttribute()
    {
        return "Contract ({$this->contract_number})";
    }

    public function getCompletenessDictionary()
    {
        return __('quote.stages');
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
