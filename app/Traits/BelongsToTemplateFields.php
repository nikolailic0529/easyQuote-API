<?php

namespace App\Traits;

use App\Models\Template\TemplateField;
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
        return $this->belongsToMany(TemplateField::class, 'quote_template_template_field')->orderBy('template_fields.order');
    }

    public function syncTemplateFields(?array $templateFields): void
    {
        if (blank($templateFields)) {
            return;
        }

        $this->templateFields()->sync($templateFields);
    }
}
