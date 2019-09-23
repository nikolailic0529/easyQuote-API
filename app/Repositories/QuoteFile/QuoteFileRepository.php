<?php namespace App\Repositories\QuoteFile;

use App\Models \ {
    User,
    Quote\Quote,
    QuoteFile\QuoteFile,
    QuoteFile\ImportableColumn,
    QuoteFile\DataSelectSeparator,
    QuoteFile\ImportedRow
};
use App\Contracts\Repositories\QuoteFile\QuoteFileRepositoryInterface;
use App\Http\Requests\StoreQuoteFileRequest;
use Illuminate\Support\LazyCollection;
use Illuminate\Pipeline\Pipeline;
use Storage, Str, File;

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
        $clientFileName = $request->quote_file->getClientOriginalName();
        $fileName = Str::limit(File::name($clientFileName), 50, '');
        $extension = File::extension($clientFileName);
        $original_file_name = "{$fileName}.{$extension}";

        $quoteFile = $request->user()->quoteFiles()->make(
            $request->merge(compact('original_file_name'))->all()
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

    public function createRowsData(QuoteFile $quoteFile, array $array)
    {
        $user = request()->user();

        /**
         * Delete early imported data
         */
        $quoteFile->columnsData()->forceDelete();
        $quoteFile->rowsData()->forceDelete();

        $importedPages = $this->createImportedPages($array, $quoteFile, $user)->all();

        $quoteFile->markAsHandled();

        $rows = collect([]);

        collect($importedPages)->where('page', '>=', $quoteFile->imported_page)->each(function ($page) use ($rows) {
            collect($page['rows'])->each(function ($row) use ($rows) {
                $rows->push($row);
            });
        });

        return $rows;
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

    protected function createRowData(array $row, QuoteFile $quoteFile, User $user, int $pageNumber, ImportedRow $importedRow, $formatHeader = true)
    {
        $rowData = collect($row)->map(function ($value, $alias) use ($quoteFile, $user, $pageNumber, $importedRow, $formatHeader) {
            $importableColumn = $this->importableColumn->whereHas('aliases', function ($query) use ($alias) {
                return $query->whereAlias($alias);
            })->first();

            if($formatHeader) {
                $alias = Str::header($alias);
            }

            // $value = $quoteFile->quote->customer->handleColumnValue($importableColumn, $value);

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

    public function getRowsData(QuoteFile $quoteFile)
    {
        return $quoteFile->rowsData()->with('columnsData')->where('page', '>=', $quoteFile->imported_page)->get();
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

    public function deletePriceListsExcept(QuoteFile $quoteFile)
    {
        $exceptedQuoteFileId = $quoteFile->id;
        $quote = $quoteFile->quote;

        $priceLists = $quote->quoteFiles()
            ->priceLists()->whereKeyNot($exceptedQuoteFileId)->get();

        $priceLists->each(function ($price) {
            $price->columnsData()->delete();
            $price->rowsData()->delete();
        });

        return $priceLists->each->delete();
    }

    public function deletePaymentSchedulesExcept(QuoteFile $quoteFile)
    {
        $exceptedQuoteFileId = $quoteFile->id;
        $quote = $quoteFile->quote;

        $paymentSchedules = $quote->quoteFiles()
            ->paymentSchedules()->whereKeyNot($exceptedQuoteFileId)->get();

        $paymentSchedules->each(function ($schedule) {
            $schedule->scheduleData()->delete();
        });

        return $paymentSchedules->each->delete();
    }

    public function deleteExcept(QuoteFile $quoteFile)
    {
        if($quoteFile->isSchedule()) {
            return $this->deletePaymentSchedulesExcept($quoteFile);
        }

        return $this->deletePriceListsExcept($quoteFile);
    }
}
