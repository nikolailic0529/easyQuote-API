<?php

namespace App\Domain\Worldwide\Resources\V1\Opportunity;

use App\Domain\User\Resources\V1\UserRelationResource;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Resources\V1\Quote\SalesOrderOfQuoteResource;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorldwideQuote
 */
class QuoteOfOpportunityResource extends JsonResource
{
    public function toArray($request): array
    {
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'user' => UserRelationResource::make($this->user),
            'quote_number' => $this->quote_number,
            'sales_order_exists' => ($this->salesOrder !== null),
            'sales_order' => SalesOrderOfQuoteResource::make($this->salesOrder),
            'permissions' => [
                'view' => $user->can('view', $this->resource),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
                'change_ownership' => $user->can('changeOwnership', $this->resource),
            ],
            'sharing_users' => UserRelationResource::collection($this->sharingUsers),
            'submitted_at' => $this->submitted_at,
        ];
    }
}
