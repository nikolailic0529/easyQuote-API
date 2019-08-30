<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuoteFileRequest;
use App\Contracts\Repositories\QuoteFile\QuoteFileRepositoryInterface;
use App\Jobs\StoreQuoteFile;

class QuoteFilesController extends Controller
{
    protected $quoteFile;
    protected $quoteFileHandler;

    public function __construct(QuoteFileRepositoryInterface $quoteFile)
    {
        $this->quoteFile = $quoteFile;
    }

    public function store(StoreQuoteFileRequest $request)
    {
        $quoteFile = $this->quoteFile->create($request);

        return response()->json(
            $quoteFile
        );
    }

    public function all()
    {
        $allQuoteFiles = $this->quoteFile->all();

        return response()->json(
            $allQuoteFiles
        );
    }
}
