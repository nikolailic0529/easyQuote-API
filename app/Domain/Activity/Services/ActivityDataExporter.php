<?php

namespace App\Domain\Activity\Services;

use App\Domain\Activity\DataTransferObjects\ActivityExportCollection;
use App\Foundation\Filesystem\TemporaryDirectory;
use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use League\Csv\Writer as CsvWriter;

class ActivityDataExporter
{
    protected Config $config;

    protected PdfWrapper $pdfWrapper;

    protected ActivityDataMapper $dataMapper;

    public function __construct(Config $config, PdfWrapper $pdfWrapper, ActivityDataMapper $dataMapper)
    {
        $this->config = $config;
        $this->pdfWrapper = $pdfWrapper;
        $this->dataMapper = $dataMapper;
    }

    public function exportToPdf(Builder $query): \SplFileInfo
    {
        $limit = 500;

        $summary = $this->getActivitySummary($query);

        error_abort_if($query->doesntExist(), ANF_01, 'ANF_01', 404);

        $activities = $query->get();

        $this->dataMapper->mapActivityLogEntities(...$activities);

        $activityCollection = ActivityExportCollection::create($summary, $activities, $limit);

        $filePath = $this->makeExportFilepath('pdf');

        $this->pdfWrapper->loadView('activities.pdf', ['activityCollection' => $activityCollection])
            ->save($filePath);

        return new \SplFileInfo($filePath);
    }

    public function exportToCsv(Builder $query): \SplFileInfo
    {
        $limit = 1000;

        $summary = $this->getActivitySummary($query);

        error_abort_if($query->doesntExist(), ANF_01, 'ANF_01', 404);

        $activities = $query->get();

        $this->dataMapper->mapActivityLogEntities(...$activities);

        $activityCollection = ActivityExportCollection::create($summary, $activities, $limit);

        $filePath = $this->makeExportFilepath('csv');

        file_put_contents($filePath, null);

        $writer = CsvWriter::createFromPath($filePath, 'w');

        if (filled($activityCollection->subjectName)) {
            $writer->insertOne([$activityCollection->subjectName]);
            $writer->insertOne([]);
        }

        /*
         * Summary
         */
        $writer->insertOne($activityCollection->summaryHeader);
        $writer->insertOne($activityCollection->summaryData);

        $writer->insertOne([]);

        /*
         * Logs
         */
        $writer->insertAll($activityCollection->collectionHeader);

        $activityCollection->collection->each(fn ($activity) => $writer->insertAll($activity));

        unset($writer);

        return new \SplFileInfo($filePath);
    }

    public function getActivitySummary(Builder $query): array
    {
        $query = $query->toBase()->cloneWithout(['columns', 'limit', 'offset']);

        $activityDescriptions = $this->config->get('activitylog.types') ?? [];

        $totals = $query->groupBy('description')
            ->select([
                DB::raw('COUNT(*) as count'),
                'description',
            ])
            ->pluck('count', 'description');

        $summary = array_merge([
            'total' => (int) $totals->sum(),
        ], $totals->all());

        return array_map(function (string $description) use ($summary) {
            $type = __('activitylog.totals.'.$description);

            return [
                'type' => $type,
                'count' => (int) ($summary[$description] ?? 0),
            ];
        }, ['total', ...$activityDescriptions]);
    }

    protected function makeExportFilepath(string $ext): string
    {
        $tempDirectory = (new TemporaryDirectory())->create();

        return $tempDirectory->path(
            sprintf('activities_%s.%s', now()->format('m-d-y_hm'), $ext)
        );
    }
}
