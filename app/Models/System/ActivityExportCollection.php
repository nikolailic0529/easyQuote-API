<?php

namespace App\Models\System;

use Illuminate\Support\{Arr, Collection};
use Exception;

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

    /**
     * ActivityExportCollection constructor.
     * @param iterable $summary
     * @param iterable $collection
     * @param int $limit
     * @param bool $isSpecifiedSubject
     * @throws Exception
     */
    public function __construct(iterable $summary, iterable $collection, int $limit, bool $isSpecifiedSubject = false)
    {
        $this->summary = Collection::wrap($summary);
        $this->collection = $this->prepareCollection($collection);
        $this->limit = $limit;

        if ($isSpecifiedSubject) {
            $this->subjectName = Collection::wrap($collection)->first()->subject->item_name ?? null;
        }
    }

    /**
     * ActivityExportCollection constructor.
     * @param iterable $summary
     * @param iterable $collection
     * @param int $limit
     * @param bool $isSpecifiedSubject
     * @return ActivityExportCollection
     * @throws Exception
     */
    public static function create(iterable $summary, iterable $collection, int $limit, bool $isSpecifiedSubject = false): ActivityExportCollection
    {
        return new static($summary, $collection, $limit, $isSpecifiedSubject);
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
            throw new Exception('You must pass the value for prependable data');
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
        return collect($collection)->map(function ($activity) use ($collection) {
            ['old' => $old, 'attributes' => $attributes] = $activity->readable_changes;

            $changes = collect($attributes)->map(function ($value, $key) use ($old) {
                $old = data_get($old, $key, ['attribute' => data_get($value, 'attribute'), 'value' => null]);
                return [$old, $value];
            });

            $firstChanges = $changes->isNotEmpty() ? $changes->shift() : array_fill(0, 4, null);

            $lines = collect();

            $firstLine = [
                $activity->subject_name,
                $activity->subject_type_base,
                $activity->description,
                $firstChanges,
                $activity->causer_name,
                $activity->created_at
            ];

            $lines->push(Arr::flatten($firstLine));

            $changes->each(function ($value) use ($lines) {
                $line = Arr::flatten([array_fill(0, 3, null), $value, array_fill(0, 2, null)]);
                return $lines->push($line);
            });

            return $lines;
        })->values();
    }
}
