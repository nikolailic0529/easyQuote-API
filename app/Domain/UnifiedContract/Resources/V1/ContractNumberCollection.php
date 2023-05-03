<?php

namespace App\Domain\UnifiedContract\Resources\V1;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Str;

class ContractNumberCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $array = [];

        foreach ($this->resource as $item) {
            $array[] = Str::replaceFirst('CQ', 'CT', $item->contract_number);
        }

        return [
            'data' => $array,
        ];
    }
}
