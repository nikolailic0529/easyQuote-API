<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Contracts\Repositories\AddressRepositoryInterface as AddressRepository;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    protected $address;

    public function __construct(AddressRepository $address)
    {
        $this->address = $address;
        $this->authorizeResource(Address::class, 'address');
    }

    /**
     * Display a listing of the address.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(
            request()->filled('search')
                ? $this->address->search(request('search'))
                : $this->address->all()
        );
    }

    /**
     * Store a newly created address in storage.
     *
     * @param  \App\Http\Requests\Address\StoreAddressRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreAddressRequest $request)
    {
        return response()->json(
            $this->address->create($request)
        );
    }

    /**
     * Display the specified address.
     *
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function show(Address $address)
    {
        return response()->json(
            $this->address->find($address->id)
        );
    }

    /**
     * Update the specified address in storage.
     *
     * @param  \App\Http\Requests\Address\UpdateAddressRequest  $request
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAddressRequest $request, Address $address)
    {
        return response()->json(
            $this->address->update($request, $address->id)
        );
    }

    /**
     * Remove the specified address from storage.
     *
     * @param  \App\Models\Address  $address
     * @return \Illuminate\Http\Response
     */
    public function destroy(Address $address)
    {
        return response()->json(
            $this->address->delete($address->id)
        );
    }

    /**
     * Activate the specified address.
     *
     * @param Address $address
     * @return \Illuminate\Http\Response
     */
    public function activate(Address $address)
    {
        $this->authorize('update', $address);

        return response()->json(
            $this->address->activate($address->id)
        );
    }

    /**
     * Deactivate the specified address.
     *
     * @param Address $address
     * @return \Illuminate\Http\Response
     */
    public function deactivate(Address $address)
    {
        $this->authorize('update', $address);

        return response()->json(
            $this->address->deactivate($address->id)
        );
    }
}
