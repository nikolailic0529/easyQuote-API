<?php namespace App\Repositories\QuoteFile;

use Illuminate\Http \ {
    Request,
    UploadedFile
};
use App\Models\User;
use App\Models\QuoteFile \ {
    QuoteFile,
    QuoteFileFormat,
    ImportableColumn,
    DataSelectSeparator,
    ImportedRow
};
use App\Contracts\Repositories\QuoteFile\QuoteFileRepositoryInterface;
use App\Http\Requests\StoreQuoteFileRequest;
use Illuminate\Support\LazyCollection;
use Storage;

class QuoteFileRepository implements QuoteFileRepositoryInterface
{
    protected $quoteFile;

    protected $dataSelectSeparator;

    protected $importableColumn;

    public function __construct(
        QuoteFile $quoteFile,
        DataSelectSeparator $dataSelectSeparator,
        ImportableColumn $importableColumn
    ) {
        $this->quoteFile = $quoteFile;
        $this->dataSelectSeparator = $dataSelectSeparator;
        $this->importableColumn = $importableColumn;
    }

    public function all()
    {
        return request()->user()->quoteFiles()->ordered()->get();
    }

    public function make(array $array)
    {
        return $this->quoteFile->make($array);
    }

    public function create(StoreQuoteFileRequest $request)
    {
        $quoteFile = $request->user()->quoteFiles()->make(
            $request->all()
        );

        $format = $request->format;

        if($request->has('data_select_separator_id') && $format->extension === 'csv') {
            $separator = $this->dataSelectSeparator->whereId($request->data_select_separator_id)->first();

            $quoteFile->dataSelectSeparator()->associate($separator);
        }

        $quoteFile->format()->associate($format);

        $quoteFile->markAsDrafted();

        if(($quoteFile->isPdf() || $quoteFile->isWord()) && $request->has('rawData'))
        {
            $this->createRawData($quoteFile, $request->rawData);
        }

        return $quoteFile->makeHidden('user');
    }

    public function createRawData(QuoteFile $quoteFile, array $array)
    {
        $user = $quoteFile->user;
        $rawFilesDirectory = "{$user->quoteFilesDirectory}/raw";

        $rawData = collect($array)->map(function ($data) use ($quoteFile, $user, $rawFilesDirectory) {
            $filePath = "{$rawFilesDirectory}/{$quoteFile->id}@{$data['page']}.txt";

            Storage::put($filePath, $data['content']);

            $importedRawData = $quoteFile->importedRawData()->make([
                'page' => $data['page'],
                'file_path' => $filePath
            ]);

            $importedRawData->user()->associate($user);

            $importedRawData->save();

            return $importedRawData;
        });

        return $rawData;
    }

    public function getRawData(QuoteFile $quoteFile)
    {
        return $quoteFile->importedRawData()->get();
    }

    public function createRowsData(QuoteFile $quoteFile, array $array, $requestedPage = false)
    {
        $user = request()->user();

        /**
         * Delete early imported data
         */
        $quoteFile->columnsData()->forceDelete();
        $quoteFile->rowsData()->forceDelete();

        $importedPages = $this->createImportedPages($array, $quoteFile, $user);

        $quoteFile->markAsHandled();

        if($requestedPage) {
            return data_get($importedPages->firstWhere('page', $requestedPage), 'rows');
        }

        return $importedPages;
    }

    public function createScheduleData(QuoteFile $quoteFile, array $value)
    {
        $user = request()->user();

        /**
         * Delete early imported payment schedule data
         */
        $quoteFile->scheduleData()->forceDelete();

        $scheduleData = $quoteFile->scheduleData()->make(compact('value'));
        $scheduleData->user()->associate($user);
        $scheduleData->save();

        $quoteFile->markAsHandled();

        return $scheduleData;
    }

    protected function createImportedPages(array $array, QuoteFile $quoteFile, User $user)
    {
        $array = collect($array)->filter(function ($item, $key) {
            return isset($item['page']) && isset($item['rows']);
        });

        $importedPages = LazyCollection::make(function () use ($array) {
            foreach ($array as $item) {
                yield $item;
            }
        })->map(function ($page, $key) use ($quoteFile, $user) {
            ['page' => $pageNumber, 'rows' => $rows] = $page;

            $importedRows = $this->createImportedRows($rows, $quoteFile, $user, $pageNumber);

            return [
                'page' => $pageNumber,
                'rows' => $importedRows
            ];
        });

        return $importedPages;
    }

    protected function createImportedRows(array $rows, QuoteFile $quoteFile, User $user, int $pageNumber)
    {
        $importedRows = collect();

        LazyCollection::make(function () use ($rows) {
            foreach($rows as $row) {
                yield $row;
            }
        })->chunk(40)->each(function ($rowsChunk, $key) use ($quoteFile, $user, $pageNumber, $importedRows) {
            $rowsChunk->each(function ($row, $key) use ($quoteFile, $user, $pageNumber, $importedRows) {
                $importedRow = $quoteFile->rowsData()->make([
                    'page' => $pageNumber
                ]);

                $importedRow->user()->associate($user);

                $importedRow->quoteFile()->associate($quoteFile);

                $importedRow->markAsDrafted();

                $rowData = $this->createRowData($row, $quoteFile, $user, $pageNumber, $importedRow);

                $importedRows->push(
                    collect($importedRow)->only('id')->merge([
                        'columns_data' => $rowData->values()
                    ])
                );
            });
        });

        return $importedRows;
    }

    protected function createRowData(array $row, QuoteFile $quoteFile, User $user, int $pageNumber, ImportedRow $importedRow)
    {
        $rowData = collect($row)->map(function ($value, $alias) use ($quoteFile, $user, $pageNumber, $importedRow) {
            $importableColumn = $this->importableColumn->whereHas('aliases', function ($query) use ($alias) {
                return $query->whereAlias($alias);
            })->first();

            $columnDataItem = $importedRow->columnsData()->make(
                [
                    'value' => $value,
                    'page' => $pageNumber,
                    'header' => $alias ?? __('parser.unknown_column_header')
                ]
            );

            $columnDataItem->user()->associate($user);

            $columnDataItem->quoteFile()->associate($quoteFile);

            if(!is_null($importableColumn)) {
                $columnDataItem->importableColumn()->associate($importableColumn);
            };

            $columnDataItem->markAsDrafted();

            return collect($columnDataItem)->only('id', 'value', 'header', 'importable_column_id')->sortKeys();
        });

        return $rowData;
    }

    public function getRowsData(QuoteFile $quoteFile, int $requestedPage = 2)
    {
        if($quoteFile->isCsv() || $quoteFile->isWord()) {
            $requestedPage = 1;
        }

        return $quoteFile->rowsData()->with('columnsData')->wherePage($requestedPage)->get();
    }

    public function getScheduleData(QuoteFile $quoteFile)
    {
        return $quoteFile->scheduleData;
    }

    public function find(String $id)
    {
        return $this->quoteFile->whereId($id)->first();
    }

    public function exists(String $id)
    {
        return $this->quoteFile->whereId($id)->exists();
    }
}
