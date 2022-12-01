<?php

namespace App\Http\Resources\V1\Opportunity;

use App\Http\Resources\V1\User\UserRelationResource;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorldwideQuote
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
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
            'submitted_at' => $this->submitted_at,
        ];
    }
}