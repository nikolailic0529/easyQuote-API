<?php namespace App\Http\Controllers\API\Quotes;

use App\Http\Controllers\Controller;
use App\Http\Requests \ {
    StoreQuoteFileRequest,
    HandleQuoteFileRequest
};
use App\Contracts \ {
    Repositories\QuoteFile\QuoteFileRepositoryInterface,
    Services\ParserServiceInterface
};
use App\Jobs\StoreQuoteFile;
use App\Models\QuoteFile\QuoteFile;

class QuoteFilesController extends Controller
{
    protected $quoteFile;

    protected $parserService;

    public function __construct(QuoteFileRepositoryInterface $quoteFile, ParserServiceInterface $parserService)
    {
        $this->quoteFile = $quoteFile;
        $this->parserService = $parserService;
    }

    public function store(StoreQuoteFileRequest $request)
    {
        $quoteFile = $this->quoteFile->create(
            $this->parserService->preHandle($request)
        );

        return response()->json(
            $quoteFile
        );
    }

    public function file(QuoteFile $quoteFile)
    {
        return response()->json(
            $quoteFile->load('format')
        );
    }

    public function all()
    {
        $allQuoteFiles = $this->quoteFile->all();

        return response()->json(
            $allQuoteFiles
        );
    }

    public function handle(HandleQuoteFileRequest $request)
    {
        return $this->parserService->handle(
            $request
        );
    }
}
