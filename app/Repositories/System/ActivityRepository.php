<?php

namespace App\Repositories\System;

use App\Contracts\Repositories\System\ActivityRepositoryInterface;
use App\Repositories\SearchableRepository;
use App\Http\Resources\ActivityCollection;
use App\Models\System\{
    Activity,
    ActivityExportCollection
};
use Illuminate\Database\Eloquent\{
    Builder,
    Model
};
use Illuminate\Support\Collection;
use League\Csv\Writer as CsvWriter;
use Str;

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

    public function subjectQuery(string $subject_id): Builder
    {
        return $this->activity->query()->whereSubjectId($subject_id);
    }

    public function all()
    {
        $summary = $this->summary();

        return $this->toCollection(parent::all())->additional(compact('summary'));
    }

    public function search(string $query = '')
    {
        $summary = $this->summary();

        return $this->toCollection(parent::search($query))->additional(compact('summary'));
    }

    public function findSubject(string $subject_id): Model
    {
        return $this->subjectQuery($subject_id)->firstOrFail()->subject;
    }

    public function subjectActivities(string $subject_id)
    {
        $summary = $this->summary($subject_id);
        $data = parent::all($this->subjectScope($subject_id));
        $subject_name = $data->getCollection()->first()->subject->item_name ?? null;

        return $this->toCollection($data)->additional(compact('summary', 'subject_name'));
    }

    public function searchSubjectActivities(string $subject_id, string $query = '')
    {
        $summary = $this->summary($subject_id);
        $data = parent::search($query, $this->subjectScope($subject_id));

        return $this->toCollection($data)->additional(compact('summary'));
    }

    public function summary(?string $subject_id = null): Collection
    {
        $types = config('activitylog.types');
        $expectedTypes = array_combine($types, array_fill(0, count($types), 0));

        $summary = $this->filterQuery($this->query())
            ->when(filled($subject_id), function ($query) use ($subject_id) {
                $query->whereSubjectId($subject_id);
            })
            ->select(['description', \DB::raw('count(*) as count')])
            ->whereIn('description', $types)
            ->groupBy('description')
            ->pluck('count', 'description')
            ->union($expectedTypes);

        $summary = $summary
            ->sortBy(function ($count, $type) use ($types) {
                return array_search($type, $types);
            })
            ->transform(function ($count, $type) {
                $type = ucfirst($type);
                return compact('type', 'count');
            })
            ->values();

        $summary->push(['type' => 'Total', 'count' => $summary->sum('count')]);

        return $summary;
    }

    public function export(string $type)
    {
        if (!$method = $this->exportMethod($type)) {
            return;
        }

        $summary = $this->summary();
        $activitiesQuery = $this->filterQuery($this->query())->latest()->limit(5000);

        error_abort_if($activitiesQuery->doesntExist(), ANF_01, 'ANF_01', 404);

        $activities = $activitiesQuery->get();

        $activityCollection = ActivityExportCollection::create($summary, $activities);

        return $this->{$method}($activityCollection);
    }

    public function exportSubject(string $subject_id, string $type)
    {
        if (!$method = $this->exportMethod($type)) {
            return;
        }

        $summary = $this->summary($subject_id);
        $activitiesQuery = $this->filterQuery($this->subjectQuery($subject_id))->latest()->limit(5000);

        error_abort_if($activitiesQuery->doesntExist(), ANF_01, 'ANF_01', 404);

        $activities = $activitiesQuery->get();

        $activityCollection = ActivityExportCollection::create($summary, $activities, true);

        return $this->{$method}($activityCollection);
    }

    public function meta(): array
    {
        $periods = collect(config('activitylog.periods'))->transform(function ($value) {
            $label = now()->period($value)->label;
            return compact('label', 'value');
        });

        $types = collect(config('activitylog.types'))->transform(function ($value) {
            $label = ucfirst($value);
            return compact('label', 'value');
        });

        $subject_types = collect(config('activitylog.subject_types'))->keys()->transform(function ($value) {
            $label = ucfirst($value);
            return compact('label', 'value');
        });

        return compact('periods', 'types', 'subject_types');
    }

    protected function subjectScope(string $subject_id)
    {
        return function ($query) use ($subject_id) {
            $query->whereSubjectId($subject_id);
        };
    }

    protected function toCollection($data)
    {
        return new ActivityCollection($data);
    }

    protected function summaryCacheKey(?string $subject_id = null): string
    {
        return filled($subject_id)
            ? "{$this->summaryCacheKey}:{$subject_id}"
            : $this->summaryCacheKey;
    }

    protected function filterQueryThrough(): array
    {
        return [
            \App\Http\Query\DefaultOrderBy::class,
            \App\Http\Query\OrderByCreatedAt::class,
            \App\Http\Query\Activity\Types::class,
            \App\Http\Query\Activity\Period::class,
            \App\Http\Query\Activity\CustomPeriod::class,
            \App\Http\Query\Activity\CauserId::class,
            \App\Http\Query\Activity\SubjectTypes::class
        ];
    }

    protected function filterableQuery()
    {
        return $this->query();
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

    protected function searchableScope($query)
    {
        return $query->with('subject');
    }

    /**
     * Export the activities as CSV.
     *
     * @param Export $activityCollection
     * @return string
     */
    protected function exportCsv(ActivityExportCollection $activityCollection): string
    {
        $filepath = $this->exportFilepath('csv');
        storage_put($filepath, null);

        $writer = CsvWriter::createFromPath(storage_real_path($filepath), 'w');

        if (filled($activityCollection->subjectName)) {
            $writer->insertOne([$activityCollection->subjectName]);
            $writer->insertOne([]);
        }

        /**
         * Summary
         */
        $writer->insertOne($activityCollection->summaryHeader);
        $writer->insertOne($activityCollection->summaryData);

        $writer->insertOne([]);

        /**
         * Logs
         */
        $writer->insertAll($activityCollection->collectionHeader);
        $activityCollection->collection->each(function ($activity) use ($writer) {
            $writer->insertAll($activity);
        });

        return storage_real_path($filepath);
    }

    /**
     * Export the activities as PDF.
     *
     * @param ActivityCollection $activityCollection
     * @return string
     */
    protected function exportPdf(ActivityExportCollection $activityCollection)
    {
        $filepath = $this->exportFilepath('pdf');

        $this->pdfWrapper()->loadView('activities.pdf', compact('activityCollection'))->save(storage_path("app/{$filepath}"));

        return storage_real_path($filepath);
    }

    /**
     * Return PdfWrapper instance.
     *
     * @return \Barryvdh\Snappy\PdfWrapper
     */
    protected function pdfWrapper()
    {
        return app('snappy.pdf.wrapper');
    }


    private function exportFilepath(string $ext): string
    {
        storage_missing('activities') && storage_mkdir('activities');

        return 'activities/' . now()->format('m-d-y_hm') . '_' . Str::random(40) . '.' . $ext;
    }

    private function exportMethod(string $type): string
    {
        $method = 'export' . ucfirst($type);

        if (!in_array(strtolower($type), ['csv', 'pdf']) || !method_exists($this, $method)) {
            return false;
        }

        return $method;
    }
}
