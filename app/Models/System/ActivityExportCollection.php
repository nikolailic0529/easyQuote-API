<?php

namespace App\Models\System;

use App\Services\Activity\ActivityDataMapper;
use Illuminate\Support\{Arr, Collection};

class ActivityExportCollection
{
    /**
     * Summary data.
     *
     * @var \Illuminate\Support\Collection
     */
    public $summary;

    /**
     * Activity data.
     *
     * @var \Illuminate\Support\Collection
     */
    public $collection;

    /**
     * Exportable Collection limit.
     *
     * @var int
     */
    public $limit;

    /**
     * Name of the specified subject.
     *
     * @var null|string
     */
    public $subjectName = null;

    public function __construct(iterable $summary, iterable $collection, int $limit, bool $isSpecifiedSubject = false)
    {
        if (!Collection::wrap($collection)->first() instanceof Activity) {
            throw new \Exception('The 2 argument must contain the Activities');
        }

        $this->summary = Collection::wrap($summary);
        $this->collection = $this->prepareCollection($collection);
        $this->limit = $limit;

        if ($isSpecifiedSubject) {
            $this->subjectName = Collection::wrap($collection)->first()->subject->item_name ?? null;
        }
    }

    public static function create(...$args)
    {
        return new static(...$args);
    }

    public function __get($key)
    {
        $method = 'get'.ucfirst($key);

        if (!method_exists($this, $method)) {
            return;
        }

        return $this->{$method}();
    }

    public function getSummaryHeader(): array
    {
        return $this->summary->pluck('type')->toArray();
    }

    public function getSummaryData(): array
    {
        return $this->summary->pluck('count')->toArray();
    }

    public function getCollectionHeader(): array
    {
        return [
            ['Subject', 'Subject Type', 'Description', 'Previous', null, 'Current', null, 'Action By', 'On'],
            [null, null, null, 'Attribute', 'Value', 'Attribute', 'Value', null, null]
        ];
    }

    public function prepend(...$args)
    {
        if (is_string($args[0]) && func_num_args() < 2) {
            throw new \Exception('You must pass the value for prependable data');
        }

        if (is_string($args[0])) {
            $this->prepends[$args[0]] = $args[1];
        }

        if (is_array($args[0])) {
            foreach ($args as $key => $value) {
                $this->prepends[$key] = $value;
            }
        }

        return $this;
    }

    protected function prepareCollection(iterable $collection)
    {
        return collect($collection)->map(function (Activity $activity) use ($collection) {
            $attributeChanges = $activity->attribute_changes ?? [];
            $oldAttributeValues = collect($attributeChanges[ActivityDataMapper::OLD_ATTRS_KEY] ?? [])->keyBy('attribute')->all();
            $newAttributeValues = collect($attributeChanges[ActivityDataMapper::NEW_ATTRS_KEY] ?? [])->keyBy('attribute')->all();

            $flattenChanges = array_map(function (string $key) use ($newAttributeValues, $oldAttributeValues) {
                $default = [
                    'attribute' => $key,
                    'value' => null
                ];

                $oldValue = Arr::get($oldAttributeValues, $key, $default);
                $newValue = Arr::get($newAttributeValues, $key, $default);

                return [$oldValue, $newValue];
            }, array_keys($newAttributeValues));

            $headChanges = value(function () use (&$flattenChanges) {

                if (!empty($flattenChanges)) {
                    return array_shift($flattenChanges);
                }

                return array_fill(0, 4, null);
            });

            $lines = collect();

            $firstLine = [
                $activity->subject_name,
                $activity->subject_type_base,
                $activity->description,
                $headChanges,
                $activity->causer_name ?? $activity->causer_service_name,
                $activity->created_at
            ];

            $lines->push(Arr::flatten($firstLine));

            foreach ($flattenChanges as $change) {
                $line = Arr::flatten([array_fill(0, 3, null), $change, array_fill(0, 2, null)]);

                return $lines->push($line);
            }

            return $lines;
        })->values();
    }
}
