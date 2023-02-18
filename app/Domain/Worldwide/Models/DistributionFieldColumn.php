<?php

namespace App\Domain\Worldwide\Models;

use App\Domain\QuoteFile\Models\ImportableColumn;
use App\Domain\Template\Models\TemplateField;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property string                                             $worldwide_distribution_id
 * @property string                                             $template_field_id
 * @property string                                             $importable_column_id
 * @property bool                                               $is_default_enabled
 * @property string                                             $default_value
 * @property string                                             $sort
 * @property string|null                                        $template_field_name
 * @property string|null                                        $template_field_header
 * @property bool|null                                          $is_editable
 * @property bool|null                                          $is_required
 * @property bool|null                                          $is_mapping_visible
 * @property bool|null                                          $is_preview_visible
 * @property \App\Domain\QuoteFile\Models\ImportableColumn|null $importableColumn
 */
class DistributionFieldColumn extends Pivot
{
    public $timestamps = false;

    public function worldwideDistribution(): BelongsTo
    {
        return $this->belongsTo(WorldwideDistribution::class);
    }

    public function templateField(): BelongsTo
    {
        return $this->belongsTo(TemplateField::class);
    }

    public function importableColumn(): BelongsTo
    {
        return $this->belongsTo(ImportableColumn::class);
    }
}
