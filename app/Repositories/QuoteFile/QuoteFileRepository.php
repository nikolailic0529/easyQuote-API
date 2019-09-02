<?php namespace App\Repositories\QuoteFile;

use Illuminate\Http \ {
    Request,
    UploadedFile
};
use App\Models\QuoteFile \ {
    QuoteFile,
    QuoteFileFormat,
    ImportableColumn
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

        $fileFormatId = $request->quote_file_format_id;

        $quoteFile = $request->user()->quoteFiles()->make(
            $request->merge([
                'original_file_path' => $filePath
            ])->all()
        );

        $quoteFile->format()->associate($format);

        $quoteFile->markAsDrafted();

        return $quoteFile;
    }
    
    public function createRawData(QuoteFile $quoteFile, Array $array)
    {
        return $quoteFile->importedRawData()->createMany(
            $array
        );
    }

    public function getRawData(QuoteFile $quoteFile, Int $page = 2)
    {
        return $quoteFile->importedRawData()->where('page', $page)->first();
    }

    public function createColumnData(QuoteFile $quoteFile, Array $array, Int $page)
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

                return $columnDataItem;
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
        $extension = explode('/', $file->getMimeType())[1];

        $format = QuoteFileFormat::whereExtension($extension)->firstOrFail();

        return $format;
    }
}
