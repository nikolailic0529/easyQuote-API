<?php

namespace App\Domain\Worldwide\Models;

use App\Domain\Pipeline\Models\Pipeline;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\User\Models\User;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class OpportunityForm.
 *
 * @property string|null                $user_id
 * @property string|null                $pipeline_id
 * @property string|null                $form_schema_id
 * @property bool|null                  $is_system
 * @property User|null                  $user
 * @property Pipeline|null              $pipeline
 * @property OpportunityFormSchema|null $formSchema
 */
class OpportunityForm extends Model implements SearchableEntity
{
    use Uuid;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function formSchema(): BelongsTo
    {
        return $this->belongsTo(OpportunityFormSchema::class);
    }

    public function getSearchIndex(): string
    {
        return $this->getTable();
    }

    public function toSearchArray(): array
    {
        return [
            'space_name' => $this->pipeline->space->space_name,
            'pipeline_name' => $this->pipeline->pipeline_name,
        ];
    }
}
