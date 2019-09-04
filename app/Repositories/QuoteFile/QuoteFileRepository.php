<?php namespace App\Repositories\QuoteFile;

use Illuminate\Http \ {
    Request,
    UploadedFile
};
use App\Models\QuoteFile \ {
    QuoteFile,
    QuoteFileFormat,
    ImportableColumn,
    DataSelectSeparator
};
use App\Contracts\Repositories\QuoteFile\QuoteFileRepositoryInterface;
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
        $tempFile = $request->file('quote_file');

        $format = $this->determineFileFormat($tempFile);

        $filePath = $tempFile->store(
            $request->user()->quoteFilesDirectory
        );

        $quoteFile = $request->user()->quoteFiles()->make(
            $request->merge([
                'original_file_path' => $filePath
            ])->all()
        );

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

    public function getRawData(QuoteFile $quoteFile, Int $page = 2)
    {
        if($quoteFile->format->extension === 'csv') {
            $quoteFile->importedRawData()->first();
        }

        return $quoteFile->importedRawData()->where('page', $page)->first();
    }

    public function createColumnData(QuoteFile $quoteFile, Array $array, $page = null)
    {
        $user = request()->user();

        $importedColumnData = collect($array)->map(function ($column, $alias) use ($quoteFile, $user, $page) {
            $importableColumn = ImportableColumn::where('alias', $alias)->firstOrFail();
            
            $rows = collect($column)->map(function ($value) use ($quoteFile, $user, $importableColumn, $page) {
                $columnDataItem = $quoteFile->columnData()->make(
                    [
                        'value' => $value,
                        'page' => $page
                    ]
                );

                $columnDataItem->user()->associate($user);
                $columnDataItem->importableColumn()->associate($importableColumn);

                $columnDataItem->markAsDrafted();

                return collect($columnDataItem)->only('value')->flatten();
            });

            return collect($importableColumn->makeHidden('regexp'))->merge(
                compact('rows')
            );
        });

        $quoteFile->markAsHandled();

        return $importedColumnData->values();
    }

    public function determineFileFormat(UploadedFile $file)
    {
        $extension = collect([explode('/', $file->getMimeType())[1]]);
        
        if($extension->first() === 'plain') {
            $extension->push('csv');
        }

        $format = QuoteFileFormat::whereIn('extension', $extension)->firstOrFail();

        return $format;
    }
}
