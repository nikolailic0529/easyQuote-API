<?php namespace App\Repositories\QuoteTemplate;

use App\Contracts\Repositories\QuoteTemplate\TemplateFieldRepositoryInterface;
use App\Models\QuoteTemplate\TemplateField;

class TemplateFieldRepository implements TemplateFieldRepositoryInterface
{
    protected $templateField;

    public function __construct(TemplateField $templateField)
    {
        $this->templateField = $templateField;
    }

    public function all()
    {
        return $this->templateField->with('templateFieldType')->ordered()->get();
    }
}