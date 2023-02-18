<?php

namespace App\Domain\Company\Resources\V1;

use App\Domain\Company\Models\Company;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
class ExternalCompanyList extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,

            'name' => $this->name,
            'type' => $this->type,
            'categories' => $this->categories->pluck('name'),

            'source' => $this->source,
            'source_long' => __($this->source),

            'vat' => $this->vat,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,

            'created_at' => $this->created_at,
            'activated_at' => $this->activated_at,
        ];
    }
}
