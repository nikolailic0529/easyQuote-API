<?php

namespace App\Domain\Rescue\Models;

use App\Domain\QuoteFile\Concerns\BelongsToImportableColumn;
use App\Domain\Rescue\Concerns\BelongsToQuote;
use App\Domain\Template\Concerns\{
    BelongsToTemplateField
};
use Illuminate\Database\Eloquent\Relations\Pivot;

class ContractFieldColumn extends Pivot
{
    use BelongsToQuote;
    use BelongsToImportableColumn;
    use BelongsToTemplateField;

    public $timestamps = false;

    protected $table = 'contract_field_column';

    protected $attributes = [
        'importable_column_id' => null,
        'is_default_enabled' => false,
        'is_preview_visible' => true,
        'default_value' => null,
        'sort' => null,
    ];

    protected $casts = [
        'is_default_enabled' => 'boolean',
        'is_preview_visible' => 'boolean',
        'default_value' => 'string',
    ];

    public static function defaultAttributesToArray(): array
    {
        return array_diff_key(static::make()->getAttributes(), array_flip(['importable_column_id']));
    }
}
