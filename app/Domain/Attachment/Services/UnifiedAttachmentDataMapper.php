<?php

namespace App\Domain\Attachment\Services;

use App\Domain\Attachment\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;

class UnifiedAttachmentDataMapper
{
    public function mapUnifiedAttachmentCollection(BaseCollection $collection): BaseCollection
    {
        $hydrated = [];

        foreach ($collection as $item) {
            $hydrated[] = $this->hydrateUnifiedAttachmentFromArray((array) $item);
        }

        return new BaseCollection($hydrated);
    }

    protected function hydrateUnifiedAttachmentFromArray(array $data): Model
    {
        $attachment = new Attachment();

        return $attachment->newFromBuilder($data);
    }
}
