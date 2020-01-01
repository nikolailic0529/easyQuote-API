<?php

namespace App\Traits;

use App\Models\QuoteTemplate\TemplateField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait BelongsToTemplateFields
{
    protected static function bootBelongsToTemplateFields()
    {
        static::created(function (Model $model) {
            $systemTemplateFields = TemplateField::system()->pluck('id');
            $model->templateFields()->syncWithoutDetaching($systemTemplateFields);
        });
    }

    public function templateFields(): BelongsToMany
    {
        return $this->belongsToMany(TemplateField::class)->ordered();
    }

    public function syncTemplateFields(?array $templateFields): void
    {
        if (blank($templateFields)) {
            return;
        }

        $this->templateFields()->sync($templateFields);
    }
}
