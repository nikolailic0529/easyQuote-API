<?php

namespace App\Models\Quote;

use App\Casts\GroupDescription;
use App\Contracts\SearchableEntity;
use App\Models\{QuoteFile\ImportableColumn, QuoteFile\ImportedRow, QuoteFile\QuoteFile, QuoteFile\ScheduleData};
use App\Traits\{
    Activatable,
    BelongsToUser,
    BelongsToCustomer,
    BelongsToCompany,
    BelongsToVendor,
    BelongsToCountry,
    Submittable,
    Reviewable,
    Completable,
    Search\Searchable,
    Quote\SwitchesMode,
    Quote\HasGroupDescriptionAttribute,
    Quote\HasAdditionalHtmlAttributes,
    QuoteTemplate\BelongsToContractTemplate,
    Activity\LogsActivity,
    Auth\Multitenantable,
    SavesPreviousState,
    Uuid
};
use Illuminate\Database\Eloquent\{
    SoftDeletes,
    Model
};
use Illuminate\Support\Traits\Tappable;
use App\Models\Template\TemplateField;
use App\Traits\{
    BelongsToQuote,
    NotifiableModel,
};
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model implements SearchableEntity
{
    use Uuid,
        Multitenantable,
        Searchable,
        BelongsToUser,
        BelongsToCustomer,
        BelongsToCompany,
        BelongsToVendor,
        BelongsToCountry,
        BelongsToContractTemplate,
        Submittable,
        Activatable,
        SoftDeletes,
        HasGroupDescriptionAttribute,
        LogsActivity,
        HasAdditionalHtmlAttributes,
        Reviewable,
        Completable,
        SwitchesMode,
        SavesPreviousState,
        Tappable,
        BelongsToQuote,
        NotifiableModel;


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
        'contract_date'
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
            'company_name'           => $this->company->name,
            'contract_number'        => $this->contract_number,
            'customer_name'          => $this->customer_name,
            'customer_rfq'           => $this->customer->rfq,
            'customer_valid_until'   => $this->customer->valid_until,
            'customer_support_start' => $this->customer->support_start,
            'customer_support_end'   => $this->customer->support_end,
            'user_fullname'          => optional($this->user)->fullname,
            'created_at'             => optional($this->created_at)->format(config('date.format')),
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
        return $this->hasOneThrough(ScheduleData::class, QuoteFile::class, 'id', null, 'schedule_file_id')->withDefault();
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
}
