<?php

namespace App\Models\Template;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Class TemplateSchema
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
        'data_headers' => 'array'
    ];
}
