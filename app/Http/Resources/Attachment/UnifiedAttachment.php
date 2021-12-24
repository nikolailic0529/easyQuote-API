<?php

namespace App\Http\Resources\Attachment;

use App\Models\Attachment;
use App\Models\Company;
use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class UnifiedAttachment extends JsonResource
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

        $parentModel = Relation::getMorphedModel($this->attachable_type) ?? $this->attachable_type;

        return [
            'id' => $this->getKey(),
            'type' => $this->type,
            'parent_entity_class' => Str::snake(class_basename($parentModel)),
            'parent_entity_type' => match ($parentModel) {
                Quote::class, WorldwideQuote::class => 'Quote',
                Company::class => 'Company',
            },
            'quote_id' => $this->quote_id,
            'customer_id' => $this->customer_id,
            'filepath' => asset('storage/attachments/'.$this->filepath),
            'filename' => $this->filename,
            'extension' => $this->extension,
            'size' => $this->size,
            'created_at' => $this->created_at,
        ];
    }
}
