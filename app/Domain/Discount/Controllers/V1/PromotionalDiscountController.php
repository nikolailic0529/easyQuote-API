<?php

namespace App\Domain\Discount\Controllers\V1;

use App\Domain\Discount\Contracts\PromotionalDiscountRepositoryInterface as PromotionalDiscountRepository;
use App\Domain\Discount\Models\{PromotionalDiscount};
use App\Domain\Discount\Requests\DeletePromotionalDiscountRequest;
use App\Domain\Discount\Requests\StorePromotionalDiscountRequest;
use App\Domain\Discount\Requests\UpdatePromotionalDiscountRequest;
use App\Domain\Discount\Resources\V1\DiscountListCollection;
use App\Foundation\Http\Controller;
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
     * @param \App\Domain\Discount\Models\PromotionalDiscount $promotion
     *
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
     * @param \App\Domain\Discount\Models\PromotionalDiscount $promotion
     *
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
     * @param \App\Domain\Discount\Models\PromotionalDiscount $promotion
     */
    public function destroy(DeletePromotionalDiscountRequest $request,
                            PromotionalDiscount $promotion): JsonResponse
    {
        return response()->json(
            $this->promotionalDiscount->delete($promotion->id)
        );
    }

    /**
     * Activate the specified Promotional Discount from storage.
     *
     * @param \App\Domain\Discount\Models\PromotionalDiscount $promotion
     *
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
     * @param \App\Domain\Discount\Models\PromotionalDiscount $promotion
     *
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
