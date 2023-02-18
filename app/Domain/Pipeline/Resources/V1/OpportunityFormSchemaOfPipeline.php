<?php

namespace App\Domain\Pipeline\Resources\V1;

use App\Domain\Pipeline\Models\Pipeline;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pipeline
 */
class OpportunityFormSchemaOfPipeline extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     */
    public function toArray($request): array
    {
        return [
            'form_data' => transform($this->opportunityForm,
                function (\App\Domain\Worldwide\Models\OpportunityForm $opportunityForm) {
                    return $opportunityForm->formSchema->form_data;
                }, []),
            'sidebar_0' => transform($this->opportunityForm,
                function (\App\Domain\Worldwide\Models\OpportunityForm $opportunityForm) {
                    return $opportunityForm->formSchema->sidebar_0;
                }, []),
        ];
    }
}
