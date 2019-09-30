<?php namespace App\Http\Controllers\API\Discounts;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\Discount\PrePayDiscountRepositoryInterface as PrePayDiscountRepository;
use App\Http\Requests\Discount \ {
    StorePrePayDiscountRequest,
    UpdatePrePayDiscountRequest
};

class PrePayDiscountController extends Controller
{
    protected $prePayDiscount;

    public function __construct(PrePayDiscountRepository $prePayDiscount)
    {
        return $this->prePayDiscount = $prePayDiscount;
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
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $id)
    {
        return response()->json(
            $this->prePayDiscount->find($id)
        );
    }

    /**
     * Update the specified PrePay Discount in storage.
     *
     * @param  UpdatePrePayDiscountRequest  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePrePayDiscountRequest $request, string $id)
    {
        return response()->json(
            $this->prePayDiscount->update($request, $id)
        );
    }

    /**
     * Remove the specified PrePay Discount from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        return response()->json(
            $this->prePayDiscount->delete($id)
        );
    }

    /**
     * Activate the specified PrePay Discount from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(string $id)
    {
        return response()->json(
            $this->prePayDiscount->activate($id)
        );
    }

    /**
     * Deactivate the specified PrePay Discount from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function deactivate(string $id)
    {
        return response()->json(
            $this->prePayDiscount->deactivate($id)
        );
    }
}
