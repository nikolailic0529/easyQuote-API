<?php

namespace App\Repositories\System;

use App\Contracts\Repositories\System\ActivityRepositoryInterface;
use App\Repositories\SearchableRepository;
use App\Http\Resources\ActivityCollection;
use App\Models\System\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ActivityRepository extends SearchableRepository implements ActivityRepositoryInterface
{
    protected $activity;

    protected $summaryCacheKey = 'activities-summary';

    public function __construct(Activity $activity)
    {
        $this->activity = $activity;
    }

    public function query(): Builder
    {
        return $this->activity->query()->with('subject');
    }

    public function all()
    {
        $summary = $this->summary();
        return (new ActivityCollection(parent::all()))->additional(compact('summary'));
    }

    public function search(string $query = '')
    {
        $summary = $this->summary();
        return (new ActivityCollection(parent::search($query)))->additional(compact('summary'));
    }

    public function subjectQuery(string $subject_id): Builder
    {
        return $this->activity->query()->whereSubjectId($subject_id);
    }

    public function summary(?string $subject_id = null): Collection
    {
        if (cache()->has($this->summaryCacheKey($subject_id))) {
            return cache($this->summaryCacheKey($subject_id));
        }

        $expectedSummaryTypes = ['created' => 0, 'updated' => 0, 'deleted' => 0];

        $summary = $this->query()
            ->when(filled($subject_id), function ($query) use ($subject_id) {
                $query->whereSubjectId($subject_id);
            })
            ->select(['description', \DB::raw('count(*) as count')])
            ->groupBy('description')
            ->pluck('count', 'description')
            ->union($expectedSummaryTypes);

        $summary = $summary
            ->sortBy(function ($count, $type) use ($expectedSummaryTypes) {
                return array_search($type, array_keys($expectedSummaryTypes));
            })
            ->transform(function ($count, $type) {
                $type = ucfirst($type);
                return compact('type', 'count');
            })
            ->values();

        $summary->push(['type' => 'Total', 'count' => $summary->sum('count')]);

        cache([$this->summaryCacheKey($subject_id) => $summary], 30);

        return $summary;
    }

    protected function summaryCacheKey(?string $subject_id = null): string
    {
        return filled($subject_id)
            ? "$this->summaryCacheKey:{$subject_id}"
            : $this->summaryCacheKey;
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\OrderByCreatedAt::class
        ];
    }

    protected function filterableQuery()
    {
        return [
            $this->query()
        ];
    }

    protected function searchableModel(): Model
    {
        return $this->activity;
    }

    protected function searchableFields(): array
    {
        return [
            'description^5', 'changed_properties^4', 'subject_name^4', 'causer_name^4', 'created_at^3'
        ];
    }

    protected function searchableScope(Builder $query)
    {
        return $query->with('subject');
    }
}
