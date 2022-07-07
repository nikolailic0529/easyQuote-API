<?php

namespace App\Http\Resources\V1\Setting;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection as BaseCollection;

class PublicSettingCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $collection = $this->collection
            ->sortBy(static function (PublicSettingResource $resource): int {
                return (int)array_search($resource->key, config('settings.public'));
            })
            ->groupBy('section')
            ->map(static fn(BaseCollection $sectionCollection): AnonymousResourceCollection => PublicSettingResource::collection($sectionCollection));

        return $collection
            ->replace(['maintenance' => $collection->get('maintenance', PublicSettingResource::collection([]))?->keyBy('key')])
            ->toArray();
    }
}
