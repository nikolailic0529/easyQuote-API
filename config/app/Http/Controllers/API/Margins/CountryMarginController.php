<?php namespace App\Http\Controllers\API\Margins;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\Margin\MarginRepositoryInterface as MarginRepository;
use App\Http\Requests\Margin \ {
    GetPercentagesCountryMarginsRequest,
    StoreCountryMarginRequest,
    UpdateCountryMarginRequest
};

class CountryMarginController extends Controller
{
    protected $margin;

    public function __construct(MarginRepository $margin)
    {
        $this->margin = $margin;
    }

    /**
     * Display a listing of the Country Margins.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(request()->filled('search')) {
            return response()->json(
                $this->margin->searchCountryMargins(request('search'))
            );
        }

        return response()->json(
            $this->margin->allCountryMargins()
        );
    }

    /**
     * Display specified Country Margin
     *
     * @param string $quote
     * @return \Illuminate\Http\Response
     */
    public function show(string $margin)
    {
        return response()->json(
            $this->margin->getCountryMargin($margin)
        );
    }

    /**
     * Store User's Country Margin
     *
     * @param StoreCountryMarginRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCountryMarginRequest $request)
    {
        return response()->json(
            $this->margin->createCountryMargin($request)
        );
    }

    /**
     * Update specified User's Country Margin
     *
     * @param UpdateCountryMarginRequest $request
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCountryMarginRequest $request)
    {
        return response()->json(
            $this->margin->updateCountryMargin($request)
        );
    }

    /**
     * Delete specified User's Country Margin
     *
     * @param string $margin
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $margin)
    {
        return response()->json(
            $this->margin->deleteCountryMargin($margin)
        );
    }

    public function percentages(GetPercentagesCountryMarginsRequest $request)
    {
        return response()->json(
            $this->margin->percentages($request)
        );
    }

    /**
     * Activate the specified Country Margin.
     *
     * @param  string  $margin
     * @return \Illuminate\Http\Response
     */
    public function activate(string $margin)
    {
        return response()->json(
            $this->margin->activateCountryMargin($margin)
        );
    }

    /**
     * Deactivate the specified Country Margin.
     *
     * @param  string  $margin
     * @return \Illuminate\Http\Response
     */
    public function deactivate(string $margin)
    {
        return response()->json(
            $this->margin->deactivateCountryMargin($margin)
        );
    }
}
