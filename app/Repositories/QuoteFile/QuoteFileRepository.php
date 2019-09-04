<?php namespace App\Repositories\QuoteFile;

use Illuminate\Http \ {
    Request,
    UploadedFile
};
use App\Models\QuoteFile \ {
    QuoteFile,
    QuoteFileFormat,
    ImportableColumn,
    DataSelectSeparator,
    ImportedRow
};
use App\Contracts \ {
    Repositories\QuoteFile\QuoteFileRepositoryInterface,
    Services\ParserServiceInterface
};
use App\Http\Requests\StoreQuoteFileRequest;
use App\Jobs\StoreQuoteFile;

class QuoteFileRepository implements QuoteFileRepositoryInterface
{
    public function all()
    {
        return request()->user()->quoteFiles()->ordered()->get();
    }

    public function make(Array $array)
    {
        return QuoteFile::make($array);
    }

    public function create(StoreQuoteFileRequest $request)
    {
        $quoteFile = $request->user()->quoteFiles()->make(
            $request->all()
        );

        $format = $request->format;

        if($request->has('data_select_separator_id') && $format->extension === 'csv') {
            $separator = DataSelectSeparator::whereId($request->data_select_separator_id)->first();

            $quoteFile->dataSelectSeparator()->associate($separator);
        }

        $quoteFile->format()->associate($format);

        $quoteFile->markAsDrafted();

        return $quoteFile;
    }
    
    public function createRawData(QuoteFile $quoteFile, Array $array)
    {
        $user = $quoteFile->user;

        $rawData = collect($array)->map(function ($data) use ($quoteFile, $user) {
            $importedRawData = $quoteFile->importedRawData()->make($data);

            $importedRawData->user()->associate($user);

            $importedRawData->save();

            return $importedRawData;
        });

        return $rawData;
    }

    public function getRawData(QuoteFile $quoteFile)
    {
        return $quoteFile->importedRawData()->get()->toArray();
    }

    public function createRowsData(QuoteFile $quoteFile, Array $array, $requestedPage = false)
    {
        $user = request()->user();

        /**
         * Delete early imported data
         */
        $quoteFile->columnsData()->forceDelete();
        $quoteFile->rowsData()->forceDelete();

        $importedPages = collect($array)->map(function ($page, $key) use ($quoteFile, $user) {
            $pageNumber = $page['page'];
            $rows = $page['rows'];

            $importedRows = collect($rows)->map(function ($row, $key) use ($quoteFile, $user, $pageNumber) {
                $importedRow = $quoteFile->rowsData()->make([
                    'page' => $pageNumber
                ]);
            
                $importedRow->user()->associate($user);
    
                $importedRow->quoteFile()->associate($quoteFile);
    
                $importedRow->markAsDrafted();
    
                $rowData = collect($row)->map(function ($value, $alias) use ($quoteFile, $user, $pageNumber, $importedRow) {
    
                    $importableColumn = ImportableColumn::where('alias', $alias)->first();
    
                    $columnDataItem = $importedRow->columnsData()->make(
                        [
                            'value' => $value,
                            'page' => $pageNumber
                        ]
                    );
    
                    $columnDataItem->user()->associate($user);
    
                    $columnDataItem->quoteFile()->associate($quoteFile);
                    
                    if(!is_null($importableColumn)) {
                        $columnDataItem->importableColumn()->associate($importableColumn);
                    }
    
                    $columnDataItem->markAsDrafted();
    
                    return collect($columnDataItem)->only('value', 'importable_column_id')->merge(
                        $importableColumn->only('header')
                    );
                });

                return collect($importedRow)->only('id')->merge([
                    'columns_data' => $rowData->values()
                ]);
            });

            return [
                'page' => $pageNumber,
                'rows' => $importedRows
            ];
        });
        
        $quoteFile->markAsHandled();

        if($requestedPage) {
            return $importedPages->firstWhere('page', $requestedPage);
        } else {
            return $importedPages;
        }
    }

    public function getRowsData(QuoteFile $quoteFile, Int $page)
    {
        $quoteFileId = $quoteFile->id;

        if($quoteFile->format->extension === 'csv') {
            $page = 1;
        }

        $rows = $quoteFile->rowsData()->wherePage($page)->with('columnsData.importableColumn')->get()
            ->each(function ($row) {
                $row->columnsData->makeHidden(['id', 'imported_row_id', 'importableColumn']);
            });

        return compact('page', 'rows');
    }

    public function get(String $id)
    {
        return QuoteFile::whereId($id)->first();
    }

    public function exists(String $id)
    {
        return QuoteFile::whereId($id)->exists();
    }
}
