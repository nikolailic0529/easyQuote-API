<?php namespace App\Models\Quote;

use App\Models \ {
    UuidModel,
    QuoteTemplate\QuoteTemplate,
    QuoteTemplate\TemplateField,
    QuoteFile\ImportableColumn
};

class Quote extends UuidModel
{
    public function templateFields()
    {
        return $this->belongsToMany(TemplateField::class, 'quote_field_column', 'quote_id');
    }

    public function quoteTemplate()
    {
        return $this->belongsTo(QuoteTemplate::class);
    }

    public function attachColumnToField(TemplateField $templateField, ImportableColumn $importableColumn)
    {
        $fieldId = $templateField->id;
        $columnId = $importableColumn->id;

        if($this->templateFields()->whereId($fieldId)->exists()) {
            return $this->templateFields()->updateExistingPivot(
                $fieldId, ['importable_column_id' => $columnId]
            );    
        }

        return $this->templateFields()->attach([
            $fieldId => ['importable_column_id' => $columnId]
        ]);
    }
}
