<?php namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories \ {
    Quote\QuoteRepositoryInterface as QuoteRepository,
    QuoteTemplate\TemplateFieldRepositoryInterface as TemplateFieldRepository,
    Quote\Margin\MarginRepositoryInterface as MarginRepository
};
use App\Http\Requests \ {
    StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    MappingReviewRequest,
    ReviewAppliedMarginRequest
};
use App\Models \ {
    Quote\Quote
};

class QuoteController extends Controller
{
    protected $quote;

    protected $templateField;

    protected $margin;

    public function __construct(
        QuoteRepository $quote,
        TemplateFieldRepository $templateField,
        MarginRepository $margin
    ) {
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

    public function step1()
    {
        return response()->json(
            $this->quote->step1()
        );
    }

    public function step2(MappingReviewRequest $request)
    {
        return response()->json(
            $this->quote->step2($request)
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

    public function step3()
    {
        return response()->json(
            $this->margin->data()
        );
    }

    public function step4(ReviewAppliedMarginRequest $request)
    {
        return response()->json(
            $this->quote->step4($request)
        );
    }

    /**
     * Get acceptable Discounts for the specified Quote
     *
     * @param string $id
     * @return \Illuminate\Http\Response
     */
    public function discounts(string $id)
    {
        return response()->json(
            $this->quote->discounts($id)
        );
    }
}
