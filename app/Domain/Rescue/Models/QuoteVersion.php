<?php

namespace App\Domain\Rescue\Models;

use App\Domain\Note\Contracts\HasOwnNotes;
use App\Domain\Note\Models\ModelHasNotes;
use App\Domain\Note\Models\Note;
use App\Domain\QuoteFile\Models\ImportableColumn;
use App\Domain\Template\Models\TemplateField;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class QuoteVersion extends BaseQuote implements HasOwnNotes
{
    protected $table = 'quote_versions';

    protected $touches = ['quote'];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function getItemNameAttribute()
    {
        $customer_rfq = $this->customer->rfq ?? 'unknown RFQ';

        return "Quote Version ({$customer_rfq} / $this->versionName)";
    }

    public function templateFields(): BelongsToMany
    {
        return $this->belongsToMany(TemplateField::class, 'quote_version_field_column', 'quote_version_id');
    }

    public function importableColumns(): BelongsToMany
    {
        return $this->belongsToMany(ImportableColumn::class, 'quote_version_field_column', $this->getForeignKey());
    }

    public function fieldsColumns(): HasMany
    {
        return $this->hasMany(QuoteVersionFieldColumn::class, 'quote_version_id')->with('templateField');
    }

    public function discounts()
    {
        return $this->belongsToMany(Discount::class, 'quote_version_discount', 'quote_version_id')
            ->withPivot('duration', 'margin_percentage')
            ->with('discountable')
            ->whereHasMorph('discountable', $this->discountsOrder())
            ->orderByRaw("field(`discounts`.`discountable_type`, {$this->discountsOrderToString()}) desc");
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
        return Attribute::get(function (): ?Note {
            return $this->notes
                ->first(static fn (Note $note): bool => $note->getFlag(Note::FROM_ENTITY_WIZARD));
        })
            ->withoutObjectCaching();
    }
}
