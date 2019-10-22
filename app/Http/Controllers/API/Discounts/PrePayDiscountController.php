<?php namespace App\Http\Controllers\API\Discounts;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\Discount\PrePayDiscountRepositoryInterface as PrePayDiscountRepository;
use App\Http\Requests\Discount \ {
    StorePrePayDiscountRequest,
    UpdatePrePayDiscountRequest
};
use App\Models\Quote\Discount \ {
    Discount,
    PrePayDiscount
};

class PrePayDiscountController extends Controller
{
    protected $prePayDiscount;

    public function __construct(PrePayDiscountRepository $prePayDiscount)
    {
        $this->prePayDiscount = $prePayDiscount;
        $this->authorizeResource(Discount::class, 'pre_pay');
    }

    /**
     * Display a listing of the PrePay Discounts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(request()->filled('search')) {
            return response()->json(
                $this->prePayDiscount->search(request('search'))
            );
        }

        return response()->json(
            $this->prePayDiscount->all()
        );
    }

    /**
     * Store a newly created PrePay Discount in storage.
     *
     * @param  StorePrePayDiscountRequest  $request
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
     * @param  \App\Models\Quote\Discount\PrePayDiscount $pre_pay
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
     * @param  UpdatePrePayDiscountRequest  $request
     * @param  \App\Models\Quote\Discount\PrePayDiscount $pre_pay
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
     * @param  \App\Models\Quote\Discount\PrePayDiscount $pre_pay
     * @return \Illuminate\Http\Response
     */
    public function destroy(PrePayDiscount $pre_pay)
    {
        return response()->json(
            $this->prePayDiscount->delete($pre_pay->id)
        );
    }

    /**
     * Activate the specified PrePay Discount from storage.
     *
     * @param  \App\Models\Quote\Discount\PrePayDiscount $pre_pay
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
     * @param  \App\Models\Quote\Discount\PrePayDiscount $pre_pay
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
