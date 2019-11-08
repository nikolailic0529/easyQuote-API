<?php namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\{
    Quote\QuoteRepositoryInterface as QuoteRepository,
    QuoteTemplate\TemplateFieldRepositoryInterface as TemplateFieldRepository,
    Quote\Margin\MarginRepositoryInterface as MarginRepository
};
use App\Http\Requests\{
    StoreQuoteStateRequest,
    GetQuoteTemplatesRequest,
    MappingReviewRequest
};
use App\Http\Requests\Quote\StoreGroupDescriptionRequest;
use App\Models\{
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
        $this->authorize('view', $quote);

        return response()->json(
            $this->quote->preparedQuote($quote->id)
        );
    }

    public function storeState(StoreQuoteStateRequest $request)
    {
        if ($request->has('quote_id')) {
            $this->authorize('update', $this->quote->find($request->quote_id));
        } else {
            $this->authorize('create', Quote::class);
        }

        return response()->json(
            $this->quote->storeState($request)
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
        $this->authorize('view', $this->quote->find($request->quote_id));

        if ($request->has('search')) {
            return response()->json(
                $this->quote->rows($request->quote_id, $request->search)
            );
        }

        return response()->json(
            $this->quote->step2($request)
        );
    }

    /**
     * Show Grouped Rows.
     *
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function rowsGroups(Quote $quote)
    {
        $this->authorize('view', $quote);

        return response()->json(
            $this->quote->rowsGroups($quote->id)
        );
    }

    public function templates(GetQuoteTemplatesRequest $request)
    {
        $templates = $this->quote->getTemplates($request);

        if ($templates->isEmpty()) {
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

    /**
     * Get acceptable Discounts for the specified Quote
     *
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function discounts(Quote $quote)
    {
        $this->authorize('view', $quote);

        return response()->json(
            $this->quote->discounts($quote->id)
        );
    }

    /**
     * Get Imported Rows Data after Applying Margins
     *
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function review(Quote $quote)
    {
        $this->authorize('view', $quote);

        return response()->json(
            $this->quote->review($quote->id)
        );
    }

    /**
     * Store Rows Group Description for specified Quote.
     *
     * @param StoreGroupDescriptionRequest $request
     * @param Quote $quote
     * @return \Illuminate\Http\Response
     */
    public function storeGroupDescription(StoreGroupDescriptionRequest $request, Quote $quote)
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->quote->createGroupDescription($request, $quote->id)
        );
    }

    /**
     * Remove specified Rows Group Description from specified Quote.
     *
     * @param Quote $quote
     * @param string $group
     * @return \Illuminate\Http\Response
     */
    public function destroyGroupDescription(Quote $quote, string $group)
    {
        $this->authorize('update', $quote);

        return response()->json(
            $this->quote->deleteGroupDescription($group, $quote->id)
        );
    }
}
