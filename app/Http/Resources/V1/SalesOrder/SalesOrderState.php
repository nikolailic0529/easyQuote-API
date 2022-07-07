<?php

namespace App\Http\Resources\V1\SalesOrder;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderState extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return parent::toArray($request);
    }
}
