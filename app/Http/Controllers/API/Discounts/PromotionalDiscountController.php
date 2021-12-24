<?php

namespace App\Http\Controllers\API\Discounts;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\Discount\PromotionalDiscountRepositoryInterface as PromotionalDiscountRepository;
use App\Http\Requests\Discount\{DeletePromotionalDiscount,
    StorePromotionalDiscountRequest,
    UpdatePromotionalDiscountRequest};
use App\Http\Resources\Discount\DiscountList;
use App\Http\Resources\Discount\DiscountListCollection;
use App\Models\Quote\Discount\{
    Discount,
    PromotionalDiscount
};
use Illuminate\Http\JsonResponse;

class PromotionalDiscountController extends Controller
{
    protected $promotionalDiscount;

    public function __construct(PromotionalDiscountRepository $promotionalDiscount)
    {
        $this->promotionalDiscount = $promotionalDiscount;
        $this->authorizeResource(PromotionalDiscount::class, 'promotion');
    }

    /**
     * Display a listing of the Promotional Discounts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $resource = request()->filled('search')
            ? $this->promotionalDiscount->search(request('search'))
            : $this->promotionalDiscount->all();

        return DiscountListCollection::make($resource);
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
     * @param  \App\Models\Quote\Discount\PromotionalDiscount $promotion
     * @return \Illuminate\Http\Response
     */
    public function show(PromotionalDiscount $promotion)
    {
        return response()->json(
            $this->promotionalDiscount->find($promotion->id)
        );
    }

    /**
     * Update the specified Promotional Discount in storage.
     *
     * @param  UpdatePromotionalDiscountRequest  $request
     * @param  \App\Models\Quote\Discount\PromotionalDiscount $promotion
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePromotionalDiscountRequest $request, PromotionalDiscount $promotion)
    {
        return response()->json(
            $this->promotionalDiscount->update($request, $promotion->id)
        );
    }

    /**
     * Remove the specified Promotional Discount from storage.
     *
     * @param DeletePromotionalDiscount $request
     * @param \App\Models\Quote\Discount\PromotionalDiscount $promotion
     * @return JsonResponse
     */
    public function destroy(DeletePromotionalDiscount $request,
                            PromotionalDiscount       $promotion): JsonResponse
    {
        return response()->json(
            $this->promotionalDiscount->delete($promotion->id)
        );
    }

    /**
     * Activate the specified Promotional Discount from storage.
     *
     * @param  \App\Models\Quote\Discount\PromotionalDiscount $promotion
     * @return \Illuminate\Http\Response
     */
    public function activate(PromotionalDiscount $promotion)
    {
        $this->authorize('update', $promotion);

        return response()->json(
            $this->promotionalDiscount->activate($promotion->id)
        );
    }

    /**
     * Deactivate the specified Promotional Discount from storage.
     *
     * @param  \App\Models\Quote\Discount\PromotionalDiscount $promotion
     * @return \Illuminate\Http\Response
     */
    public function deactivate(PromotionalDiscount $promotion)
    {
        $this->authorize('update', $promotion);

        return response()->json(
            $this->promotionalDiscount->deactivate($promotion->id)
        );
    }
}
