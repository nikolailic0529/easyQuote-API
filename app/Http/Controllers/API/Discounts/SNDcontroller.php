<?php

namespace App\Http\Controllers\API\Discounts;

use App\Http\Controllers\Controller;
use App\Contracts\Repositories\Quote\Discount\SNDrepositoryInterface as SNDrepository;
use App\Http\Requests\Discount\StoreSNDrequest;
use App\Http\Requests\Discount\UpdateSNDrequest;

class SNDcontroller extends Controller
{
    protected $snd;

    public function __construct(SNDrepository $snd)
    {
        return $this->snd = $snd;
    }

    /**
     * Display a listing of the SN discounts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(request()->filled('search')) {
            return response()->json(
                $this->snd->search(request('search'))
            );
        }

        return response()->json(
            $this->snd->all()
        );
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
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show(string $id)
    {
        return response()->json(
            $this->snd->find($id)
        );
    }

    /**
     * Update the specified SN discount in storage.
     *
     * @param  UpdateSNDrequest  $request
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateSNDrequest $request, string $id)
    {
        return response()->json(
            $this->snd->update($request, $id)
        );
    }

    /**
     * Remove the specified SN discount from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        return response()->json(
            $this->snd->delete($id)
        );
    }

    /**
     * Activate the specified SN discount from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function activate(string $id)
    {
        return response()->json(
            $this->snd->activate($id)
        );
    }

    /**
     * Deactivate the specified SN discount from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function deactivate(string $id)
    {
        return response()->json(
            $this->snd->deactivate($id)
        );
    }
}
