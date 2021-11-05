<?php

namespace App\Http\Controllers\API\Discounts;

use App\Contracts\Repositories\Quote\Discount\PrePayDiscountRepositoryInterface as PrePayDiscountRepository;
use App\Http\Controllers\Controller;
use App\Http\Requests\Discount\{DeletePrePayDiscount, StorePrePayDiscountRequest, UpdatePrePayDiscountRequest};
use App\Http\Resources\Discount\DiscountListCollection;
use App\Models\Quote\Discount\{PrePayDiscount};
use Illuminate\Http\JsonResponse;

class PrePayDiscountController extends Controller
{
    protected $prePayDiscount;

    public function __construct(PrePayDiscountRepository $prePayDiscount)
    {
        $this->prePayDiscount = $prePayDiscount;
        $this->authorizeResource(PrePayDiscount::class, 'pre_pay');
    }

    /**
     * Display a listing of the PrePay Discounts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $resource = request()->filled('search')
            ? $this->prePayDiscount->search(request('search'))
            : $this->prePayDiscount->all();

        return DiscountListCollection::make($resource);
    }

    /**
     * Store a newly created PrePay Discount in storage.
     *
     * @param StorePrePayDiscountRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePrePayDiscountRequest $request)
    {
        return response()->json(
            $this->prePayDiscount->create($request)
        );
    }

    /**
     * Display the specified PrePay Discount.
     *
     * @param \App\Models\Quote\Discount\PrePayDiscount $pre_pay
     * @return \Illuminate\Http\Response
     */
    public function show(PrePayDiscount $pre_pay)
    {
        return response()->json(
            $this->prePayDiscount->find($pre_pay->id)
        );
    }

    /**
     * Update the specified PrePay Discount in storage.
     *
     * @param UpdatePrePayDiscountRequest $request
     * @param \App\Models\Quote\Discount\PrePayDiscount $pre_pay
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePrePayDiscountRequest $request, PrePayDiscount $pre_pay)
    {
        return response()->json(
            $this->prePayDiscount->update($request, $pre_pay->id)
        );
    }

    /**
     * Remove the specified PrePay Discount from storage.
     *
     * @param DeletePrePayDiscount $request
     * @param \App\Models\Quote\Discount\PrePayDiscount $pre_pay
     * @return JsonResponse
     */
    public function destroy(DeletePrePayDiscount $request,
                            PrePayDiscount       $pre_pay): JsonResponse
    {
        return response()->json(
            $this->prePayDiscount->delete($pre_pay->id)
        );
    }

    /**
     * Activate the specified PrePay Discount from storage.
     *
     * @param \App\Models\Quote\Discount\PrePayDiscount $pre_pay
     * @return \Illuminate\Http\Response
     */
    public function activate(PrePayDiscount $pre_pay)
    {
        $this->authorize('update', $pre_pay);

        return response()->json(
            $this->prePayDiscount->activate($pre_pay->id)
        );
    }

    /**
     * Deactivate the specified PrePay Discount from storage.
     *
     * @param \App\Models\Quote\Discount\PrePayDiscount $pre_pay
     * @return \Illuminate\Http\Response
     */
    public function deactivate(PrePayDiscount $pre_pay)
    {
        $this->authorize('update', $pre_pay);

        return response()->json(
            $this->prePayDiscount->deactivate($pre_pay->id)
        );
    }
}
