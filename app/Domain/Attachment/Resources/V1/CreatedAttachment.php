<?php

namespace App\Domain\Attachment\Resources\V1;

use App\Domain\Attachment\Models\Attachment;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attachment
 */
class CreatedAttachment extends JsonResource
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
            'id' => $this->getKey(),
            'user' => $this->owner,
            'type' => $this->type,
            'filepath' => \asset('storage/attachments/'.$this->filepath),
            'filename' => $this->filename,
            'extension' => $this->extension,
            'size' => $this->size,
            'created_at' => $this->{$this->getCreatedAtColumn()}?->format(\config('date.format_time')),
        ];
    }
}
