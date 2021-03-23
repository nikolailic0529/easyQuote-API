<?php

namespace App\Http\Controllers\API\WorldwideQuotes;

use App\Http\Controllers\Controller;
use App\Http\Requests\WorldwideQuoteNote\CreateWorldwideQuoteNote;
use App\Http\Requests\WorldwideQuoteNote\UpdateWorldwideQuoteNote;
use App\Http\Resources\Note\WorldwideQuoteNoteList;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteNote;
use App\Queries\Exceptions\ValidationException;
use App\Queries\WorldwideQuoteNoteQueries;
use App\Services\WorldwideQuoteNoteService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WorldwideQuoteNoteController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * Paginate the existing Notes of Worldwide Quote.
     *
     * @param Request $request
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteNoteQueries $queries
     * @return JsonResponse
     * @throws ValidationException
     * @throws AuthorizationException
     */
    public function paginateQuoteNotes(Request $request, WorldwideQuote $worldwideQuote, WorldwideQuoteNoteQueries $queries)
    {
        $this->authorize('view', $worldwideQuote);
        $this->authorize('viewAny', WorldwideQuoteNote::class);

        $paginator = $queries->paginateWorldwideQuoteNoteQuery($worldwideQuote->getKey(), $request)->apiPaginate();

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
     * @param CreateWorldwideQuoteNote $request
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteNoteService $service
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function storeQuoteNote(CreateWorldwideQuoteNote $request, WorldwideQuote $worldwideQuote, WorldwideQuoteNoteService $service): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);
        $this->authorize('create', WorldwideQuoteNote::class);

        return response()->json(
            $service->createWorldwideQuoteNote($request->input('text'), $worldwideQuote, $request->user()),
            Response::HTTP_CREATED
        );
    }

    /**
     * Show the specified Note of Worldwide Quote.
     *
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteNote $worldwideQuoteNote
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showQuoteNote(WorldwideQuote $worldwideQuote, WorldwideQuoteNote $worldwideQuoteNote): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);
        $this->authorize('view', $worldwideQuoteNote);

        return response()->json(
            $worldwideQuoteNote,
            Response::HTTP_OK);
    }

    /**
     * Update the specified Note of Worldwide Quote.
     *
     * @param UpdateWorldwideQuoteNote $request
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteNote $worldwideQuoteNote
     * @param WorldwideQuoteNoteService $service
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function updateQuoteNote(UpdateWorldwideQuoteNote $request, WorldwideQuote $worldwideQuote, WorldwideQuoteNote $worldwideQuoteNote, WorldwideQuoteNoteService $service): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);
        $this->authorize('update', $worldwideQuoteNote);

        return response()->json(
            $service->updateWorldwideQuoteNote($request->input('text'), $worldwideQuoteNote),
            Response::HTTP_OK
        );
    }

    /**
     * Delete the specified Note from Worldwide Quote.
     *
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteNote $worldwideQuoteNote
     * @param WorldwideQuoteNoteService $service
     * @return Response
     * @throws AuthorizationException
     */
    public function destroyQuoteNote(WorldwideQuote $worldwideQuote, WorldwideQuoteNote $worldwideQuoteNote, WorldwideQuoteNoteService $service): Response
    {
        $this->authorize('view', $worldwideQuote);
        $this->authorize('delete', $worldwideQuoteNote);

        $service->deleteWorldwideQuoteNote($worldwideQuoteNote);

        return response()->noContent();
    }
}
