<?php

namespace App\Http\Controllers\API\Discounts;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\Discount\SNDrepositoryInterface as SNDrepository;
use App\Http\Requests\Discount\{
    StoreSNDrequest,
    UpdateSNDrequest
};
use App\Http\Resources\Discount\DiscountList;
use App\Http\Resources\Discount\DiscountListCollection;
use App\Models\Quote\Discount\{
    Discount,
    SND
};

class SNDcontroller extends Controller
{
    protected $snd;

    public function __construct(SNDrepository $snd)
    {
        $this->snd = $snd;
        $this->authorizeResource(SND::class, 'snd');
    }

    /**
     * Display a listing of the SN discounts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $resource = request()->filled('search')
            ? $this->snd->search(request('search'))
            : $this->snd->all();

        return DiscountListCollection::make($resource);
    }

    /**
     * Store a newly created SN discount in storage.
     *
     * @param  StoreSNDrequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSNDrequest $request)
    {
        return response()->json(
            $this->snd->create($request)
        );
    }

    /**
     * Display the specified SN discount.
     *
     * @param  \App\Models\Quote\Discount\SND $snd
     * @return \Illuminate\Http\Response
     */
    public function show(SND $snd)
    {
        return response()->json(
            $this->snd->find($snd->id)
        );
    }

    /**
     * Update the specified SN discount in storage.
     *
     * @param  UpdateSNDrequest  $request
     * @param  \App\Models\Quote\Discount\SND $snd
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSNDrequest $request, SND $snd)
    {
        return response()->json(
            $this->snd->update($request, $snd->id)
        );
    }

    /**
     * Remove the specified SN discount from storage.
     *
     * @param  \App\Models\Quote\Discount\SND $snd
     * @return \Illuminate\Http\Response
     */
    public function destroy(SND $snd)
    {
        return response()->json(
            $this->snd->delete($snd->id)
        );
    }

    /**
     * Activate the specified SN discount from storage.
     *
     * @param  \App\Models\Quote\Discount\SND $snd
     * @return \Illuminate\Http\Response
     */
    public function activate(SND $snd)
    {
        $this->authorize('update', $snd);

        return response()->json(
            $this->snd->activate($snd->id)
        );
    }

    /**
     * Deactivate the specified SN discount from storage.
     *
     * @param  \App\Models\Quote\Discount\SND $snd
     * @return \Illuminate\Http\Response
     */
    public function deactivate(SND $snd)
    {
        $this->authorize('update', $snd);

        return response()->json(
            $this->snd->deactivate($snd->id)
        );
    }
}
