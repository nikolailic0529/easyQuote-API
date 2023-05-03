<?php

namespace App\Domain\Template\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TemplateSchema.
 *
 * @property array|null $form_data
 * @property array|null $data_headers
 */
class TemplateSchema extends Model
{
    use Uuid;

    protected $guarded = [];

    protected $casts = [
        'form_data' => 'array',
        'data_headers' => 'array',
    ];
}
