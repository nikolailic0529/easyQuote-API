<?php

namespace App\Http\Resources\Setting;

use Illuminate\Http\Resources\Json\ResourceCollection;

class SettingCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $collection = $this->collection->groupBy('section')->map(fn ($section) => SettingResource::collection($section));

        $collection = $collection->replace(['maintenance' => $collection->get('maintenance')->keyBy('key')]);

        return $collection;
    }
}
