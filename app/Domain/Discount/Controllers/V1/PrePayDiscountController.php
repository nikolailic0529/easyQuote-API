<?php

namespace App\Domain\Discount\Controllers\V1;

use App\Domain\Discount\Contracts\PrePayDiscountRepositoryInterface as PrePayDiscountRepository;
use App\Domain\Discount\Models\{PrePayDiscount};
use App\Domain\Discount\Requests\DeletePrePayDiscountRequest;
use App\Domain\Discount\Requests\StorePrePayDiscountRequest;
use App\Domain\Discount\Requests\UpdatePrePayDiscountRequest;
use App\Domain\Discount\Resources\V1\DiscountListCollection;
use App\Foundation\Http\Controller;
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
     * @param \App\Domain\Discount\Models\PrePayDiscount $pre_pay
     *
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
     * @param \App\Domain\Discount\Models\PrePayDiscount $pre_pay
     *
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
     * @param \App\Domain\Discount\Models\PrePayDiscount $pre_pay
     */
    public function destroy(DeletePrePayDiscountRequest $request,
                            PrePayDiscount $pre_pay): JsonResponse
    {
        return response()->json(
            $this->prePayDiscount->delete($pre_pay->id)
        );
    }

    /**
     * Activate the specified PrePay Discount from storage.
     *
     * @param \App\Domain\Discount\Models\PrePayDiscount $pre_pay
     *
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
     * @param \App\Domain\Discount\Models\PrePayDiscount $pre_pay
     *
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
