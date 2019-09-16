<?php namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories \ {
    Customer\CustomerRepositoryInterface as CustomerRepository,
    Quote\QuoteRepositoryInterface as QuoteRepository,
    QuoteTemplate\TemplateFieldRepositoryInterface as TemplateFieldRepository
};
use App\Http\Requests \ {
    StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    FindQuoteTemplateRequest
};

class QuoteController extends Controller
{
    protected $customer;

    protected $quote;

    protected $templateField;

    public function __construct(
        CustomerRepository $customer,
        QuoteRepository $quote,
        TemplateFieldRepository $templateField
    ) {
        $this->customer = $customer;
        $this->quote = $quote;
        $this->templateField = $templateField;
    }

    public function storeState(StoreQuoteStateRequest $request)
    {
        return $this->quote->storeState(
            $request
        );
    }

    public function customers()
    {
        return response()->json(
            $this->customer->all()
        );
    }

    public function step1()
    {
        return response()->json(
            $this->quote->step1()
        );
    }

    public function templates(GetQuoteTemplatesRequest $request)
    {
        $templates = $this->quote->getTemplates($request);

        if($templates->isEmpty()) {
            abort(404, __('template.404'));
        }

        return response()->json($templates);
    }

    public function step2(FindQuoteTemplateRequest $request)
    {
        return response()->json(
            $this->quote->step2($request)
        );
    }
}
