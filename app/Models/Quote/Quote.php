<?php namespace App\Models\Quote;

use App\Models \ {
    UuidModel,
    QuoteTemplate\QuoteTemplate,
    QuoteTemplate\TemplateField,
    QuoteFile\ImportableColumn
};
use App\Traits \ {
    HasQuoteFiles,
    BelongsToUser,
    BelongsToCustomer,
    BelongsToCompany,
    Draftable
};

class Quote extends UuidModel
{
    use HasQuoteFiles, BelongsToUser, BelongsToCustomer, BelongsToCompany, Draftable;

    protected $fillable = ['customer_id', 'company_id', 'vendor_id', 'language_id', 'quote_template_id'];

    public function templateFields()
    {
        return $this->belongsToMany(TemplateField::class, 'quote_field_column', 'quote_id');
    }

    public function importableColumns()
    {
        return $this->belongsToMany(ImportableColumn::class, 'quote_field_column', 'quote_id');
    }

    public function quoteTemplate()
    {
        return $this->belongsTo(QuoteTemplate::class);
    }

    public function attachColumnToField(TemplateField $templateField, ImportableColumn $importableColumn)
    {
        $fieldId = $templateField->id;
        $importable_column_id = $importableColumn->id;

        if($this->templateFields()->whereId($fieldId)->exists()) {
            return $this->templateFields()->updateExistingPivot(
                $fieldId, compact('importable_column_id')
            );    
        }

        return $this->templateFields()->attach([
            $fieldId => compact('importable_column_id')
        ]);
    }
}
