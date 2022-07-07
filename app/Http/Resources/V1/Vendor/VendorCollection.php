<?php

namespace App\Http\Resources\V1\Vendor;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class VendorCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var VendorCollection|Collection $this */

        return $this
            ->collection
            ->sortBy(function (Vendor $vendor) {
                if ('LEN' === $vendor->short_code) {
                    return -1;
                }

                return 0;
            })
            ->map
            ->toArray($request)
            ->all();
    }
}
