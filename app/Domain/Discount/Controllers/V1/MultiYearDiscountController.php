<?php

namespace App\Domain\Discount\Controllers\V1;

use App\Domain\Discount\Contracts\MultiYearDiscountRepositoryInterface as MultiYearDiscountRepository;
use App\Domain\Discount\Models\{MultiYearDiscount};
use App\Domain\Discount\Requests\DeleteMultiYearDiscountRequest;
use App\Domain\Discount\Requests\StoreMultiYearDiscountRequest;
use App\Domain\Discount\Requests\UpdateMultiYearDiscountRequest;
use App\Domain\Discount\Resources\V1\DiscountListCollection;
use App\Foundation\Http\Controller;

class MultiYearDiscountController extends Controller
{
    protected $multiYearDiscount;

    public function __construct(MultiYearDiscountRepository $multiYearDiscount)
    {
        $this->multiYearDiscount = $multiYearDiscount;
        $this->authorizeResource(MultiYearDiscount::class, 'multi_year');
    }

    /**
     * Display a listing of the MultiYear Discounts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $resource = request()->filled('search')
            ? $this->multiYearDiscount->search(request('search'))
            : $this->multiYearDiscount->all();

        return DiscountListCollection::make($resource);
    }

    /**
     * Store a newly created MultiYear Discount in storage.
     *
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
     * @param \App\Domain\Discount\Models\MultiYearDiscount $multi_year
     *
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
     * @param \App\Domain\Discount\Models\MultiYearDiscount $multi_year
     *
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
     * @param DeleteMultiYearDiscountRequest                $deleteMultiYearDiscount
     * @param \App\Domain\Discount\Models\MultiYearDiscount $multi_year
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(DeleteMultiYearDiscountRequest $request,
                            MultiYearDiscount $multi_year)
    {
        return response()->json(
            $this->multiYearDiscount->delete($multi_year->id)
        );
    }

    /**
     * Activate the specified MultiYear Discount from storage.
     *
     * @param \App\Domain\Discount\Models\MultiYearDiscount $multi_year
     *
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
     * @param \App\Domain\Discount\Models\MultiYearDiscount $multi_year
     *
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
