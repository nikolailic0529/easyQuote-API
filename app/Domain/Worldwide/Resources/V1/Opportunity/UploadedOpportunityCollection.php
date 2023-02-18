<?php

namespace App\Domain\Worldwide\Resources\V1\Opportunity;

use App\Domain\Worldwide\DataTransferObjects\Opportunity\BatchOpportunityUploadResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BatchOpportunityUploadResult
 */
class UploadedOpportunityCollection extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     */
    public function toArray($request): array
    {
        return [
            'opportunities' => UploadedOpportunityResource::collection(collect($this->opportunities)),
            'errors' => $this->errors,
        ];
    }
}
