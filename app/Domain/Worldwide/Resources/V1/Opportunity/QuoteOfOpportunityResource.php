<?php

namespace App\Domain\Worldwide\Resources\V1\Opportunity;

use App\Domain\User\Resources\V1\UserRelationResource;
use App\Domain\Worldwide\Resources\V1\Quote\SalesOrderOfQuoteResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Worldwide\Models\WorldwideQuote
 */
class QuoteOfOpportunityResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'user' => UserRelationResource::make($this->user),
            'quote_number' => $this->quote_number,
            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
            'sales_order_exists' => ($this->salesOrder !== null),
            'sales_order' => SalesOrderOfQuoteResource::make($this->salesOrder),
            'submitted_at' => $this->submitted_at,
        ];
    }
}
