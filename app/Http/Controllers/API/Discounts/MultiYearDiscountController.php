<?php namespace App\Http\Controllers\API\Discounts;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\Discount\MultiYearDiscountRepositoryInterface as MultiYearDiscountRepository;
use App\Http\Requests\Discount \ {
    StoreMultiYearDiscountRequest,
    UpdateMultiYearDiscountRequest
};
use App\Models\Quote\Discount \ {
    Discount,
    MultiYearDiscount
};

class MultiYearDiscountController extends Controller
{

    protected $multiYearDiscount;

    public function __construct(MultiYearDiscountRepository $multiYearDiscount)
    {
        $this->multiYearDiscount = $multiYearDiscount;
        $this->authorizeResource(Discount::class, 'multi_year');
    }

    /**
     * Display a listing of the MultiYear Discounts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(request()->filled('search')) {
            return response()->json(
                $this->multiYearDiscount->search(request('search'))
            );
        }

        return response()->json(
            $this->multiYearDiscount->all()
        );
    }

    /**
     * Store a newly created MultiYear Discount in storage.
     *
     * @param  StoreMultiYearDiscountRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreMultiYearDiscountRequest $request)
    {
        return response()->json(
            $this->multiYearDiscount->create($request)
        );
    }

    /**
     * Display the specified MultiYear Discount.
     *
     * @param  \App\Models\Quote\Discount\MultiYearDiscount $multi_year
     * @return \Illuminate\Http\Response
     */
    public function show(MultiYearDiscount $multi_year)
    {
        return response()->json(
            $this->multiYearDiscount->find($multi_year->id)
        );
    }

    /**
     * Update the specified MultiYear Discount in storage.
     *
     * @param  UpdateMultiYearDiscountRequest  $request
     * @param  \App\Models\Quote\Discount\MultiYearDiscount $multi_year
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMultiYearDiscountRequest $request, MultiYearDiscount $multi_year)
    {
        return response()->json(
            $this->multiYearDiscount->update($request, $multi_year->id)
        );
    }

    /**
     * Remove the specified MultiYear Discount from storage.
     *
     * @param  \App\Models\Quote\Discount\MultiYearDiscount $multi_year
     * @return \Illuminate\Http\Response
     */
    public function destroy(MultiYearDiscount $multi_year)
    {
        return response()->json(
            $this->multiYearDiscount->delete($multi_year->id)
        );
    }

    /**
     * Activate the specified MultiYear Discount from storage.
     *
     * @param  \App\Models\Quote\Discount\MultiYearDiscount $multi_year
     * @return \Illuminate\Http\Response
     */
    public function activate(MultiYearDiscount $multi_year)
    {
        $this->authorize('update', $multi_year);

        return response()->json(
            $this->multiYearDiscount->activate($multi_year->id)
        );
    }

    /**
     * Deactivate the specified MultiYear Discount from storage.
     *
     * @param  \App\Models\Quote\Discount\MultiYearDiscount $multi_year
     * @return \Illuminate\Http\Response
     */
    public function deactivate(MultiYearDiscount $multi_year)
    {
        $this->authorize('update', $multi_year);

        return response()->json(
            $this->multiYearDiscount->deactivate($multi_year->id)
        );
    }
}
