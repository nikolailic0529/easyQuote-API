<?php

namespace App\Domain\Worldwide\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OpportunityFormSchema.
 *
 * @property array|null $form_data
 * @property array|null $sidebar_0
 */
class OpportunityFormSchema extends Model
{
    use Uuid;

    protected $guarded = [];

    protected $casts = [
        'form_data' => 'array',
        'sidebar_0' => 'array',
    ];
}
