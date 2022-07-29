<?php

namespace App\Http\Resources\V1\Attachment;

use App\Models\Attachment;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityAttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Attachment|self $this */

        return [
            'id' => $this->getKey(),
            'type' => $this->type,
            'parent_entity_type' => 'Opportunity',
            'filepath' => asset('storage/attachments/'.$this->filepath),
            'filename' => $this->filename,
            'extension' => $this->extension,
            'size' => $this->size,
            'created_at' => $this->created_at,
        ];
    }
}
