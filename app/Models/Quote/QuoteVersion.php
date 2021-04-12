<?php

namespace App\Models\Quote;

use App\Models\QuoteFile\ImportableColumn;
use App\Models\Template\TemplateField;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteVersion extends BaseQuote
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
}
