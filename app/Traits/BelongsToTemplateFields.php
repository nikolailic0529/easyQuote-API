<?php namespace App\Traits;

use App\Models\QuoteTemplate\TemplateField;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTemplateFields
{
    protected static function bootBelongsToTemplateFields()
    {
        static::created(function (Model $model) {
            $systemTemplateFields = TemplateField::system()->pluck('id');

            $model->templateFields()->syncWithoutDetaching($systemTemplateFields);
        });
    }

    public function templateFields()
    {
        return $this->belongsToMany(TemplateField::class)->ordered();
    }
}
