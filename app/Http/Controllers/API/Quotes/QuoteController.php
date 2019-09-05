<?php namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories \ {
    Quote\QuoteRepositoryInterface as QuoteRepository,
    QuoteTemplate\TemplateFieldRepositoryInterface as TemplateFieldRepository
};

class QuoteController extends Controller
{
    protected $quote;

    protected $templateField;

    public function __construct(QuoteRepository $quote, TemplateFieldRepository $templateField)
    {
        $this->quote = $quote;
        $this->templateField = $templateField;
    }

    public function step1()
    {
        return response()->json(
            $this->quote->step1()
        );
    }

    public function step2()
    {
        return response()->json(
            $this->templateField->all()
        );
    }
}