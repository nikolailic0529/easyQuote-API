<?php

namespace App\Domain\Rescue\Controllers\V1;

use App\Domain\Note\Models\Note;
use App\Domain\Note\Queries\NoteQueries;
use App\Domain\Note\Resources\V1\QuoteNoteCollection;
use App\Domain\Note\Resources\V1\QuoteNoteResource;
use App\Domain\Note\Services\NoteEntityService;
use App\Domain\Rescue\Models\{Quote};
use App\Domain\Rescue\Requests\CreateQuoteNoteRequest;
use App\Domain\Rescue\Requests\UpdateQuoteNoteRequest;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class QuoteNoteController extends Controller
{
    /**
     * Paginate notes of quote.
     *
     * @throws AuthorizationException
     */
    public function index(Request $request,
                          NoteQueries $queries,
                          Quote $quote): JsonResponse
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
     * @throws AuthorizationException
     */
    public function store(CreateQuoteNoteRequest $request,
                          NoteEntityService $entityService,
                          Quote $quote): JsonResponse
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
     * @throws AuthorizationException
     */
    public function update(UpdateQuoteNoteRequest $request,
                           NoteEntityService $entityService,
                           Quote $quote,
                           Note $note): JsonResponse
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
     * @throws AuthorizationException
     */
    public function destroy(Request $request,
                            NoteEntityService $entityService,
                            Quote $quote,
                            Note $note): Response
    {
        $this->authorize('update', $quote);
        $this->authorize('delete', $note);

        $entityService
            ->setCauser($request->user())
            ->deleteNote($note);

        return response()->noContent();
    }
}
