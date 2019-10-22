<?php namespace App\Http\Controllers\API\Margins;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\Margin\MarginRepositoryInterface as MarginRepository;
use App\Http\Requests\Margin \ {
    StoreCountryMarginRequest,
    UpdateCountryMarginRequest
};
use App\Models\Quote\Margin \ {
    Margin,
    CountryMargin
};

class CountryMarginController extends Controller
{
    protected $margin;

    public function __construct(MarginRepository $margin)
    {
        $this->margin = $margin;
        // $this->authorizeResource(Margin::class, 'margin');
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
                $this->margin->search(request('search'))
            );
        }

        return response()->json(
            $this->margin->all()
        );
    }

    /**
     * Display specified Country Margin
     *
     * @param \App\Models\Quote\Margin\CountryMargin $margin
     * @return \Illuminate\Http\Response
     */
    public function show(CountryMargin $margin)
    {
        return response()->json(
            $this->margin->find($margin->id)
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
            $this->margin->create($request)
        );
    }

    /**
     * Update specified User's Country Margin
     *
     * @param UpdateCountryMarginRequest $request
     * @param \App\Models\Quote\Margin\CountryMargin $margin
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCountryMarginRequest $request, CountryMargin $margin)
    {
        return response()->json(
            $this->margin->update($request, $margin->id)
        );
    }

    /**
     * Delete specified User's Country Margin
     *
     * @param \App\Models\Quote\Margin\CountryMargin $margin
     * @return \Illuminate\Http\Response
     */
    public function destroy(CountryMargin $margin)
    {
        return response()->json(
            $this->margin->delete($margin->id)
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
            $this->margin->activate($margin)
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
            $this->margin->deactivate($margin)
        );
    }
}
