<?php

namespace App\Http\Resources\V1\Opportunity;

use App\DTO\Opportunity\BatchOpportunityUploadResult;
use Illuminate\Http\Resources\Json\JsonResource;

class UploadedOpportunities extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        /** @var UploadedOpportunities|BatchOpportunityUploadResult $this */

        return [
            'opportunities' => UploadedOpportunity::collection(collect($this->opportunities)),
            'errors' => $this->errors,
        ];
    }
}
