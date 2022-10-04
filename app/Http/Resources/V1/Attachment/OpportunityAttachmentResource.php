<?php

namespace App\Http\Resources\V1\Attachment;

use App\Models\Attachment;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attachment
 */
class OpportunityAttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var Authorizable $user */
        $user = $request->user();

        return [
            'id' => $this->getKey(),
            'type' => $this->type,
            'parent_entity_type' => 'Opportunity',
            'filepath' => asset('storage/attachments/'.$this->filepath),
            'filename' => $this->filename,
            'extension' => $this->extension,
            'size' => $this->size,
            'permissions' => [
                'update' => $user->can('update', $this->resource),
                'delete' => $user->can('delete', $this->resource),
            ],
            'created_at' => $this->created_at,
        ];
    }
}
