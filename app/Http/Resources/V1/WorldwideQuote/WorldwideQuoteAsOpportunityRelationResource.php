<?php

namespace App\Http\Resources\V1\WorldwideQuote;

use App\Models\Quote\WorldwideQuote;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorldwideQuote */
class WorldwideQuoteAsOpportunityRelationResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'opportunity_id' => $this->opportunity()->getParentKey(),
            'contract_type_name' => $this->contractType->type_short_name,
            'quote_number' => $this->quote_number,
            'submitted_at' => $this->submitted_at,
            'permissions' => [
                'create' => $user->can('create', WorldwideQuote::class),
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
            'created_at' => $this->{$this->getCreatedAtColumn()},
            'updated_at' => $this->{$this->getUpdatedAtColumn()},
        ];
    }
}