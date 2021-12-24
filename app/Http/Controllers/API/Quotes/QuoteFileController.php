<?php

namespace App\Http\Controllers\API\Quotes;

use App\Contracts\Services\ManagesDocumentProcessors;
use App\Contracts\Services\QuoteState;
use App\Http\Controllers\Controller;
use App\Http\Requests\{HandleQuoteFileRequest, StoreQuoteFileRequest};
use App\Models\QuoteFile\QuoteFile;
use App\Services\QuoteFileService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class QuoteFileController extends Controller
{
    /**
     * Show the specific quote file.
     *
     * @param QuoteFile $file
     * @return JsonResponse
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
     * @param StoreQuoteFileRequest $request
     * @param QuoteFileService $service
     * @return JsonResponse
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
     * @param HandleQuoteFileRequest $request
     * @param QuoteState $quoteStateProcessor
     * @param ManagesDocumentProcessors $service
     * @param QuoteState $quoteProcessor
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function processQuoteFile(HandleQuoteFileRequest    $request,
                                     ManagesDocumentProcessors $service,
                                     QuoteState                $quoteProcessor): JsonResponse
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
