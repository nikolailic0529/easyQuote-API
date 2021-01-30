<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;

class ActivityCollection extends ResourceCollection
{
    public $collects = ActivityResource::class;

    public function appendSubjectName(): ActivityCollection
    {
        $this->additional($this->additional + [
                'subject_name' => optional($this->collection->first())->subject_name
            ]);

        return $this;
    }

    public function toArray($request)
    {
        if (isset($this->additional['summary'])) {
            $summary = Collection::wrap($this->additional['summary'])->pluck('count', 'type');

            $this->additional['summary'] = array_map(function (string $type) use ($summary) {
                return [
                    'type' => __('activitylog.totals.'.$type),
                    'count' => $summary->get($type) ?? 0,
                ];
            }, config('activitylog.types'));
        }

        return parent::toArray($request);
    }
}
