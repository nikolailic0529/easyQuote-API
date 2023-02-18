<?php

namespace App\Domain\Discount\Controllers\V1;

use App\Domain\Discount\Contracts\SNDrepositoryInterface as SNDrepository;
use App\Domain\Discount\Models\{SND};
use App\Domain\Discount\Requests\DeleteSpecialNegotiationDiscountRequest;
use App\Domain\Discount\Requests\StoreSndRequest;
use App\Domain\Discount\Requests\UpdateSndRequest;
use App\Domain\Discount\Resources\V1\DiscountListCollection;
use App\Foundation\Http\Controller;
use Illuminate\Http\JsonResponse;

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
     * @return \Illuminate\Http\Response
     */
    public function store(StoreSndRequest $request)
    {
        return response()->json(
            $this->snd->create($request)
        );
    }

    /**
     * Display the specified SN discount.
     *
     * @param \App\Domain\Discount\Models\SND $snd
     *
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
     * @param \App\Domain\Discount\Models\SND $snd
     *
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSndRequest $request, SND $snd)
    {
        return response()->json(
            $this->snd->update($request, $snd->id)
        );
    }

    /**
     * Remove the specified SN discount from storage.
     *
     * @param \App\Domain\Discount\Models\SND $snd
     */
    public function destroy(DeleteSpecialNegotiationDiscountRequest $request,
                            SND $snd): JsonResponse
    {
        return response()->json(
            $this->snd->delete($snd->id)
        );
    }

    /**
     * Activate the specified SN discount from storage.
     *
     * @param \App\Domain\Discount\Models\SND $snd
     *
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
     * @param \App\Domain\Discount\Models\SND $snd
     *
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
