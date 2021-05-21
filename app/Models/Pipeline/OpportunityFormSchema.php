<?php

namespace App\Models\Pipeline;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OpportunityFormSchema
 *
 * @property array|null $form_data
 */
class OpportunityFormSchema extends Model
{
    use Uuid;

    protected $guarded = [];

    protected $casts = [
        'form_data' => 'array'
    ];
}
