<?php

namespace App\Domain\Margin\Controllers\V1;

use App\Domain\Margin\Contracts\MarginRepositoryInterface as MarginRepository;
use App\Domain\Margin\Models\CountryMargin;
use App\Domain\Margin\Requests\StoreCountryMarginRequest;
use App\Domain\Margin\Requests\UpdateCountryMarginRequest;
use App\Foundation\Http\Controller;

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
        return response()->json(
            request()->filled('search')
                ? $this->margin->search(request('search'))
                : $this->margin->all()
        );
    }

    /**
     * Display specified Country Margin.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(CountryMargin $margin)
    {
        return response()->json(
            $this->margin->find($margin->id)
        );
    }

    /**
     * Store User's Country Margin.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCountryMarginRequest $request)
    {
        return response()->json(
            $this->margin->create($request)
        );
    }

    /**
     * Update specified User's Country Margin.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCountryMarginRequest $request, CountryMargin $margin)
    {
        return response()->json(
            $this->margin->update($request, $margin->id)
        );
    }

    /**
     * Delete specified User's Country Margin.
     *
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
     * @return \Illuminate\Http\Response
     */
    public function deactivate(string $margin)
    {
        return response()->json(
            $this->margin->deactivate($margin)
        );
    }
}
