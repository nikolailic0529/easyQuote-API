<?php

namespace App\Http\Controllers\API\Quotes;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuoteFileRequest;
use App\Contracts\Repositories\QuoteFileRepositoryInterface;

class QuoteFilesController extends Controller
{
    protected $quoteFile;

    public function __construct(QuoteFileRepositoryInterface $quoteFile)
    {
        $this->quoteFile = $quoteFile;
    }

    public function store(StoreQuoteFileRequest $request)
    {
        $file = $request->file('quote_file');
        $filePath = $file->store(
            $request->user()->quoteFilesDirectory
        );

        return response()->json(compact('filePath'));
    }
}
