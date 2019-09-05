<?php namespace App\Repositories\QuoteTemplate;

use App\Contracts\Repositories\QuoteTemplate\TemplateFieldRepositoryInterface;
use App\Models\QuoteTemplate\TemplateField;

class TemplateFieldRepository implements TemplateFieldRepositoryInterface
{
    public function all()
    {
        return TemplateField::orderBy('order', 'asc')->get();
    }
}