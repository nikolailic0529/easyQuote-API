<?php

namespace App\Http\Resources\Attachment;

use App\Models\Attachment;
use Illuminate\Http\Resources\Json\JsonResource;
use function asset;
use function config;
use function optional;

class AttachmentOfCompany extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Attachment|self $this */

        return [
            'id' => $this->getKey(),
            'type' => $this->type,
            'parent_entity_type' => 'Company',
            'filepath' => asset('storage/attachments/'.$this->filepath),
            'filename' => $this->filename,
            'extension' => $this->extension,
            'size' => $this->size,
            'created_at' => $this->created_at,
        ];
    }
}
