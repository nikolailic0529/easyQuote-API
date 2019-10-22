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
use App\Models\QuoteFile\QuoteFile;

class QuoteFilesController extends Controller
{
    protected $quoteFile;

    protected $parserService;

    public function __construct(QuoteFileRepositoryInterface $quoteFile, ParserServiceInterface $parserService)
    {
        $this->quoteFile = $quoteFile;
        $this->parserService = $parserService;
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

    public function store(StoreQuoteFileRequest $request)
    {
        $quoteFile = $this->quoteFile->create(
            $this->parserService->preHandle($request)
        );

        return response()->json(
            $quoteFile
        );
    }

    public function handle(HandleQuoteFileRequest $request)
    {
        $this->authorize('handle', $this->quoteFile->find($request->quote_file_id));

        return $this->parserService->handle($request);
    }
}
