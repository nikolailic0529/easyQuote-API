<?php

namespace App\Domain\Worldwide\Resources\V1\Quote;

use App\Domain\Worldwide\Models\SalesOrder;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SalesOrder
 */
class SalesOrderOfQuoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'order_number' => $this->order_number,
            'order_date' => $this->order_date,
            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
            'submitted_at' => $this->submitted_at,
        ];
    }
}
