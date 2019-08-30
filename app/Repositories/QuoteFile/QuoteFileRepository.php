<?php

namespace App\Repositories\QuoteFile;

use Illuminate\Http\Request;
use App\Models\QuoteFile \ {
    QuoteFile,
    QuoteFileFormat
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
        
        $filePath = $tempFile->store(
            $request->user()->quoteFilesDirectory
        );

        $fileFormatId = $request->quote_file_format_id;

        $quoteFile = $request->user()->quoteFiles()->make(
            $request->merge([
                'original_file_path' => $filePath
            ])->all()
        );

        $quoteFile->format()->associate($fileFormatId);

        $quoteFile->markAsDrafted();

        return $quoteFile;
    }
}