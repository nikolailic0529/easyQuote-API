<?php

namespace App\Http\Resources\Attachment;

use Illuminate\Http\Resources\Json\JsonResource;
use function asset;
use function config;
use function optional;

class CreatedAttachment extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'filepath' => asset('storage/attachments/'.$this->filepath),
            'filename' => $this->filename,
            'extension' => $this->extension,
            'size' => $this->size,
            'created_at' => optional($this->created_at)->format(config('date.format_time')),
        ];
    }
}
