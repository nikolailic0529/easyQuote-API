<?php

namespace App\Domain\Worldwide\Models;

use App\Domain\ContractType\Models\ContractType;
use App\Domain\Currency\Models\Currency;
use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Discount\Models\SND;
use App\Domain\Note\Contracts\HasOwnNotes;
use App\Domain\Note\Models\ModelHasNotes;
use App\Domain\Note\Models\Note;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Task\Models\ModelHasTasks;
use App\Domain\Task\Models\Task;
use App\Domain\User\Models\User;
use App\Domain\Vendor\Models\Vendor;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
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
                    ->whereColumn('vendors.id', 'worldwide_quote_assets.vendor_id')->limit(1)->toBase(),
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
        return $this->belongsTo(\App\Domain\Company\Models\Company::class);
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
