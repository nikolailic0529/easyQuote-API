<?php

namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Http\Requests\{
    StoreQuoteFileRequest,
    HandleQuoteFileRequest
};
use App\Contracts\Repositories\QuoteFile\QuoteFileRepositoryInterface;
use App\Contracts\Services\ManagesDocumentProcessors;
use App\Contracts\Services\QuoteState;
use App\Models\QuoteFile\QuoteFile;
use App\Services\QuoteFileService;
use Illuminate\Http\Response;

class QuoteFilesController extends Controller
{
    protected $quoteFile;

    public function __construct(QuoteFileRepositoryInterface $quoteFile)
    {
        $this->quoteFile = $quoteFile;

        $this->authorizeResource(QuoteFile::class, 'file');
    }

    public function index()
    {
        return response()->json(
            $this->quoteFile->all()
        );
    }

    public function show(QuoteFile $file)
    {
        return response()->json(
            $file->load('format')
        );
    }

    public function store(StoreQuoteFileRequest $request, QuoteFileService $service)
    {
        return response()->json(
            $service->storeQuoteFile($request->file('quote_file'), $request->user(), $request->input('file_type')),
            Response::HTTP_CREATED
        );
    }

    public function handle(HandleQuoteFileRequest $request, ManagesDocumentProcessors $service, QuoteState $quoteProcessor)
    {
        $this->authorize('handle', $request->getQuoteFile());

        $quoteProcessor->createNewVersionIfNonCreator($request->getQuote());

        return response()->json(
            $service->performProcess(
                $request->getQuote(),
                $request->getQuoteFile(),
                $request->input('page')
            )
        );
    }
}
