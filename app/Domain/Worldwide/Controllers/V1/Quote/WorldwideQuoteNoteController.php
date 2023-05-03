<?php

namespace App\Domain\Worldwide\Controllers\V1\Quote;

use App\Domain\Note\Models\Note;
use App\Domain\Note\Queries\NoteQueries;
use App\Domain\Note\Resources\V1\WorldwideQuoteNoteList;
use App\Domain\Note\Services\NoteEntityService;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Requests\Quote\CreateWorldwideQuoteNoteRequest;
use App\Domain\Worldwide\Requests\Quote\UpdateWorldwideQuoteNoteRequest;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class WorldwideQuoteNoteController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Paginate the existing Notes of Worldwide Quote.
     *
     * @throws AuthorizationException
     */
    public function paginateQuoteNotes(Request $request,
                                       NoteQueries $queries,
                                       WorldwideQuote $worldwideQuote): AnonymousResourceCollection
    {
        $this->authorize('view', $worldwideQuote);
        $this->authorize('viewAny', Note::class);

        $paginator = $queries->listNotesOfModelQuery($worldwideQuote, $request)->apiPaginate();

        return tap(WorldwideQuoteNoteList::collection($paginator), function (AnonymousResourceCollection $resourceCollection) use ($paginator) {
            $resourceCollection->additional([
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'last_page' => $paginator->lastPage(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ]);
        });
    }

    /**
     * Store a new Note for Worldwide Quote.
     *
     * @throws AuthorizationException
     */
    public function storeQuoteNote(CreateWorldwideQuoteNoteRequest $request,
                                   NoteEntityService $entityService,
                                   WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);
        $this->authorize('create', Note::class);

        $note = $entityService
            ->setCauser($request->user())
            ->createNoteForModel($request->getCreateNoteData(), $worldwideQuote);

        return response()->json($note, BaseResponse::HTTP_CREATED);
    }

    /**
     * Show the specified Note of Worldwide Quote.
     *
     * @throws AuthorizationException
     */
    public function showQuoteNote(WorldwideQuote $worldwideQuote, Note $note): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);
        $this->authorize('view', $note);

        return response()->json(
            $note,
            BaseResponse::HTTP_OK);
    }

    /**
     * Update the specified Note of Worldwide Quote.
     *
     * @throws AuthorizationException
     */
    public function updateQuoteNote(UpdateWorldwideQuoteNoteRequest $request,
                                    NoteEntityService $entityService,
                                    WorldwideQuote $worldwideQuote,
                                    Note $note): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);
        $this->authorize('update', $note);

        $note = $entityService
            ->setCauser($request->user())
            ->updateNote($note, $request->getUpdateNoteData());

        return response()->json(
            $note,
            BaseResponse::HTTP_OK
        );
    }

    /**
     * Delete the specified Note from Worldwide Quote.
     *
     * @throws AuthorizationException
     */
    public function destroyQuoteNote(Request $request,
                                     WorldwideQuote $worldwideQuote,
                                     NoteEntityService $entityService,
                                     Note $note): Response
    {
        $this->authorize('view', $worldwideQuote);
        $this->authorize('delete', $note);

        $entityService
            ->setCauser($request->user())
            ->deleteNote($note);

        return response()->noContent();
    }
}
