<?php namespace App\Http\Controllers\API\Discounts;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\Discount\MultiYearDiscountRepositoryInterface as MultiYearDiscountRepository;
use App\Http\Requests\Discount \ {
    StoreMultiYearDiscountRequest,
    UpdateMultiYearDiscountRequest
};

class MultiYearDiscountController extends Controller
{

    protected $multiYearDiscount;

    public function __construct(MultiYearDiscountRepository $multiYearDiscount)
    {
        return $this->multiYearDiscount = $multiYearDiscount;
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
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $id)
    {
        return response()->json(
            $this->multiYearDiscount->find($id)
        );
    }

    /**
     * Update the specified MultiYear Discount in storage.
     *
     * @param  UpdateMultiYearDiscountRequest  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMultiYearDiscountRequest $request, string $id)
    {
        return response()->json(
            $this->multiYearDiscount->update($request, $id)
        );
    }

    /**
     * Remove the specified MultiYear Discount from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        return response()->json(
            $this->multiYearDiscount->delete($id)
        );
    }

    /**
     * Activate the specified MultiYear Discount from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(string $id)
    {
        return response()->json(
            $this->multiYearDiscount->activate($id)
        );
    }

    /**
     * Deactivate the specified MultiYear Discount from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function deactivate(string $id)
    {
        return response()->json(
            $this->multiYearDiscount->deactivate($id)
        );
    }
}
