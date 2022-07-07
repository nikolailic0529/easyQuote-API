<?php

namespace App\Http\Controllers\API\V1\Quotes;

use App\Http\Controllers\Controller;
use App\Http\Requests\{QuoteNote\CreateQuoteNoteRequest, QuoteNote\UpdateQuoteNoteRequest,};
use App\Http\Resources\{V1\Note\QuoteNoteCollection, V1\Note\QuoteNoteResource,};
use App\Models\Note\Note;
use App\Models\Quote\{Quote,};
use App\Queries\NoteQueries;
use App\Services\Note\NoteEntityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\{JsonResponse, Request, Response};
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class QuoteNoteController extends Controller
{
    /**
     * Paginate notes of quote.
     *
     * @param Request $request
     * @param Quote $quote
     * @param NoteQueries $queries
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function index(Request     $request,
                          NoteQueries $queries,
                          Quote       $quote): JsonResponse
    {
        $this->authorize('view', $quote);

        $paginator = $queries->listNotesOfModelQuery($quote, $request)->apiPaginate();

        return response()->json(
            QuoteNoteCollection::make($paginator),
        );
    }

    /**
     * Create note for quote.
     *
     * @param CreateQuoteNoteRequest $request
     * @param NoteEntityService $entityService
     * @param Quote $quote
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(CreateQuoteNoteRequest $request,
                          NoteEntityService      $entityService,
                          Quote                  $quote): JsonResponse
    {
        $this->authorize('update', $quote);

        $note = $entityService
            ->setCauser($request->user())
            ->createNoteForModel($request->getCreateNoteData(), $quote);

        return response()->json(
            QuoteNoteResource::make($note),
            BaseResponse::HTTP_CREATED
        );
    }

    /**
     * Show note of quote.
     *
     * @param Quote $quote
     * @param Note $note
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function show(Quote $quote, Note $note): JsonResponse
    {
        $this->authorize('view', $quote);

        return response()->json(QuoteNoteResource::make($note));
    }

    /**
     * Update note of quote.
     *
     * @param UpdateQuoteNoteRequest $request
     * @param NoteEntityService $entityService
     * @param Quote $quote
     * @param Note $note
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function update(UpdateQuoteNoteRequest $request,
                           NoteEntityService      $entityService,
                           Quote                  $quote,
                           Note                   $note): JsonResponse
    {
        $this->authorize('update', $quote);
        $this->authorize('update', $note);

        $note = $entityService
            ->setCauser($request->user())
            ->updateNote($note, $request->getUpdateNoteData());

        return response()->json(QuoteNoteResource::make($note));
    }

    /**
     * Delete note of quote.
     *
     * @param Quote $quote
     * @param NoteEntityService $entityService
     * @param Note $note
     * @return Response
     * @throws AuthorizationException
     */
    public function destroy(Request           $request,
                            NoteEntityService $entityService,
                            Quote             $quote,
                            Note              $note): Response
    {
        $this->authorize('update', $quote);
        $this->authorize('delete', $note);

        $entityService
            ->setCauser($request->user())
            ->deleteNote($note);

        return response()->noContent();
    }
}
