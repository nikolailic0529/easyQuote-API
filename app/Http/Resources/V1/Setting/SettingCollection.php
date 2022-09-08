<?php

namespace App\Http\Resources\V1\Setting;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SettingCollection extends ResourceCollection
{
    protected array $onlyKeys = [];

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $collection = $this->collection;

        if (count($this->onlyKeys)) {
            $collection = $collection->whereIn('key', $this->onlyKeys);
        }

        $collection = $collection->groupBy('section')->map(fn ($section) => SettingResource::collection($section));

        $collection = $collection->replace(['maintenance' => optional($collection->get('maintenance'))->keyBy('key')]);

        return $collection;
    }

    /** @return $this */
    public function only(string ...$keys): static
    {
        $this->onlyKeys = $keys;

        return $this;
    }
}
