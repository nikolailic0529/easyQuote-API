<?php

namespace App\Domain\Rescue\Controllers\V1;

use App\Domain\DocumentProcessing\Contracts\ManagesDocumentProcessors;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\QuoteFile\Requests\HandleQuoteFileRequest;
use App\Domain\QuoteFile\Requests\{StoreQuoteFileRequest};
use App\Domain\QuoteFile\Services\QuoteFileService;
use App\Domain\Rescue\Contracts\QuoteState;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class QuoteFileController extends Controller
{
    /**
     * Show the specific quote file.
     *
     * @throws AuthorizationException
     */
    public function showQuoteFile(QuoteFile $file): JsonResponse
    {
        $this->authorize('view', $file);

        return response()->json(
            $file->load('format')
        );
    }

    /**
     * Store a new quote file.
     *
     * @throws AuthorizationException
     */
    public function storeQuoteFile(StoreQuoteFileRequest $request, QuoteFileService $service): JsonResponse
    {
        $this->authorize('create', QuoteFile::class);

        $resource = $service->storeQuoteFile(
            file: $request->file('quote_file'),
            user: $request->user(),
            fileType: $request->input('file_type')
        );

        return response()->json(
            $resource,
            Response::HTTP_CREATED
        );
    }

    /**
     * Process the specific rescue quote file.
     *
     * @param \App\Domain\Rescue\Contracts\QuoteState $quoteStateProcessor
     *
     * @throws AuthorizationException
     */
    public function processQuoteFile(HandleQuoteFileRequest $request,
                                     ManagesDocumentProcessors $service,
                                     QuoteState $quoteProcessor): JsonResponse
    {
        $this->authorize('handle', $request->getQuoteFile());

        $quoteProcessor->createNewVersionIfNonCreator($request->getQuote());

        $result = $quoteProcessor->processQuoteFileImport(
            quote: $request->getQuote(),
            quoteFile: $request->getQuoteFile(),
            importablePageNumber: $request->input('page'),
            dataSeparatorReference: $request->input('data_select_separator_id'),
        );

        return response()->json(
            $result
        );
    }
}
