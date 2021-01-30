<?php

namespace App\Services\Activity;

use App\Models\System\ActivityExportCollection;
use App\Queries\ActivityQueries;
use Barryvdh\Snappy\PdfWrapper;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use League\Csv\CannotInsertRecord;
use League\Csv\Writer as CsvWriter;

class ActivityExporter
{
    protected ActivityQueries $queries;

    protected PdfWrapper $pdfWrapper;

    public function __construct(ActivityQueries $queries, PdfWrapper $pdfWrapper)
    {
        $this->queries = $queries;
        $this->pdfWrapper = $pdfWrapper;
    }

    /**
     * @param string $format
     * @return Response
     * @throws Exception
     */
    public function export(string $format): Response
    {
        $summary = $this->queries->activitySummaryQuery()->get()->each(function (object $aggregate) {
            $aggregate->type = __('activitylog.types.'.$aggregate->type);
        });

        $collection = $this->queries->filteredActivityQuery()->limit($limit = 1000)->get();

        $activityCollection = ActivityExportCollection::create($summary, $collection, $limit);

        if ($format === 'pdf') {
            return $this->exportCollectionToPdf($activityCollection);
        } elseif ($format === 'csv') {
            return $this->exportCollectionToCsv($activityCollection);
        }

        throw new Exception("Unsupported format, $format.");
    }

    /**
     * @param string $format
     * @param string $subjectId
     * @return Response
     * @throws Exception
     */
    public function exportSubject(string $format, string $subjectId): Response
    {
        $summary = $this->queries->activitySummaryBySubjectQuery($subjectId)->get()->each(function (object $aggregate) {
            $aggregate->type = __('activitylog.types.'.$aggregate->type);
        });

        $collection = $this->queries->filteredActivityBySubjectQuery($subjectId)->limit($limit = 1000)->get();

        $activityCollection = ActivityExportCollection::create($summary, $collection, $limit);

        if ($format === 'pdf') {
            return $this->exportCollectionToPdf($activityCollection);
        } elseif ($format === 'csv') {
            return $this->exportCollectionToPdf($activityCollection);
        }

        throw new Exception("Unsupported format, $format.");
    }

    /**
     * @param ActivityExportCollection $collection
     * @return Response
     * @throws CannotInsertRecord
     */
    protected function exportCollectionToCsv(ActivityExportCollection $collection): Response
    {
        $filename = $this->exportFileName('csv');

        $writer = CsvWriter::createFromString();

        if (filled($collection->subjectName)) {
            $writer->insertOne([$collection->subjectName]);
            $writer->insertOne([]);
        }

        /**
         * Summary
         */
        $writer->insertOne($collection->summaryHeader);
        $writer->insertOne($collection->summaryData);

        $writer->insertOne([]);

        /**
         * Logs
         */
        $writer->insertAll($collection->collectionHeader);

        foreach ($collection->collection as $activity) {
            $writer->insertAll($activity);
        }

        return new Response($writer->getContent(), 200, array(
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"'
        ));
    }

    /**
     * @param ActivityExportCollection $collection
     * @return Response
     */
    protected function exportCollectionToPdf(ActivityExportCollection $collection): Response
    {
        $fileName = $this->exportFileName('pdf');

        return $this->pdfWrapper
            ->loadView('activities.pdf', [
                'activityCollection' => $collection
            ])->download($fileName);
    }

    private function exportFileName(string $ext): string
    {
        return now()->format('m-d-y_hm').'_'.Str::random(40).'.'.$ext;
    }
}
