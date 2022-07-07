<?php

namespace App\Models\Quote;

use App\Contracts\HasOwnNotes;
use App\Contracts\SearchableEntity;
use App\Models\Company;
use App\Models\ContractType;
use App\Models\Data\Currency;
use App\Models\ModelHasTasks;
use App\Models\Note\ModelHasNotes;
use App\Models\Note\Note;
use App\Models\Opportunity;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Models\Task\Task;
use App\Models\Template\QuoteTemplate;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WorldwideQuoteAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

abstract class BaseWorldwideQuote extends Model implements SearchableEntity, HasOwnNotes
{
    public function assets(): HasMany
    {
        return $this->hasMany(WorldwideQuoteAsset::class)
            ->addSelect([
                'vendor_short_code' => Vendor::query()->select('short_code')
                    ->from('vendors')
                    ->whereColumn('vendors.id', 'worldwide_quote_assets.vendor_id')->limit(1)->toBase()
            ]);
    }

    public function contractType(): BelongsTo
    {
        return $this->belongsTo(ContractType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class)->withTrashed();
    }

    public function quoteCurrency(): BelongsTo
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
        return $this->belongsTo(Company::class);
    }

    public function worldwideDistributions(): MorphMany
    {
        return $this->morphMany(WorldwideDistribution::class, 'worldwide_quote')
            // the clause is required to sync Worldwide Distribution with Opportunity Supplier Entities
            ->has('opportunitySupplier');
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

    public function tasks(): MorphToMany
    {
        return $this->morphToMany(Task::class, name: 'model', table: (new ModelHasTasks())->getTable());
    }

    public function getSearchIndex(): string
    {
        return $this->getTable();
    }

    public function toSearchArray(): array
    {
        return [
            'rfq_number' => $this->quote_number,
            'customer_name' => optional($this->company)->name,
            'valid_until_date' => $this->opportunity->opportunity_closing_date,
            'support_start_date' => $this->opportunity->opportunity_start_date,
            'support_end_date' => $this->opportunity->opportunity_end_date,
            'created_at' => optional($this->created_at)->toDateString(),
        ];
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
}
