<?php namespace App\Http\Controllers\API\Discounts;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\Discount\PromotionalDiscountRepositoryInterface as PromotionalDiscountRepository;
use App\Http\Requests\Discount \ {
    StorePromotionalDiscountRequest,
    UpdatePromotionalDiscountRequest
};

class PromotionalDiscountController extends Controller
{
    protected $promotionalDiscount;

    public function __construct(PromotionalDiscountRepository $promotionalDiscount)
    {
        return $this->promotionalDiscount = $promotionalDiscount;
    }

    /**
     * Display a listing of the Promotional Discounts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(request()->filled('search')) {
            return response()->json(
                $this->promotionalDiscount->search(request('search'))
            );
        }

        return response()->json(
            $this->promotionalDiscount->all()
        );
    }

    /**
     * Store a newly created Promotional Discount in storage.
     *
     * @param  StorePromotionalDiscountRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePromotionalDiscountRequest $request)
    {
        return response()->json(
            $this->promotionalDiscount->create($request)
        );
    }

    /**
     * Display the specified Promotional Discount.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $id)
    {
        return response()->json(
            $this->promotionalDiscount->find($id)
        );
    }

    /**
     * Update the specified Promotional Discount in storage.
     *
     * @param  UpdatePromotionalDiscountRequest  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePromotionalDiscountRequest $request, string $id)
    {
        return response()->json(
            $this->promotionalDiscount->update($request, $id)
        );
    }

    /**
     * Remove the specified Promotional Discount from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        return response()->json(
            $this->promotionalDiscount->delete($id)
        );
    }

    /**
     * Activate the specified Promotional Discount from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(string $id)
    {
        return response()->json(
            $this->promotionalDiscount->activate($id)
        );
    }

    /**
     * Deactivate the specified Promotional Discount from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function deactivate(string $id)
    {
        return response()->json(
            $this->promotionalDiscount->deactivate($id)
        );
    }
}
