<?php namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories \ {
    Customer\CustomerRepositoryInterface as CustomerRepository,
    Quote\QuoteRepositoryInterface as QuoteRepository,
    QuoteTemplate\TemplateFieldRepositoryInterface as TemplateFieldRepository,
    Quote\Margin\MarginRepositoryInterface as MarginRepository
};
use App\Http\Requests \ {
    StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    FindQuoteTemplateRequest
};
use App\Models \ {
    Quote\Quote,
    Customer\Customer
};

class QuoteController extends Controller
{
    protected $customer;

    protected $quote;

    protected $templateField;

    protected $margin;

    public function __construct(
        CustomerRepository $customer,
        QuoteRepository $quote,
        TemplateFieldRepository $templateField,
        MarginRepository $margin
    ) {
        $this->customer = $customer;
        $this->quote = $quote;
        $this->templateField = $templateField;
        $this->margin = $margin;
    }

    public function quote(Quote $quote)
    {
        return response()->json(
            $this->quote->find($quote->id)
        );
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

    public function customer(Customer $customer)
    {
        return response()->json($customer);
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

    public function drafted()
    {
        return response()->json(
            $this->quote->getDrafted()
        );
    }

    public function step3()
    {
        return response()->json(
            $this->margin->data()
        );
    }
}
