<?php

namespace App\Http\Resources\V1\Attachment;

use App\Http\Resources\V1\User\UserRelationResource;
use App\Models\Attachment;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attachment
 */
class AttachmentOfQuote extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->getKey(),
            'user' => UserRelationResource::make($this->owner),
            'type' => $this->type,
            'parent_entity_type' => 'Quote',
            'filepath' => asset('storage/attachments/'.$this->filepath),
            'filename' => $this->filename,
            'extension' => $this->extension,
            'size' => $this->size,
            'created_at' => $this->created_at,
        ];
    }
}
