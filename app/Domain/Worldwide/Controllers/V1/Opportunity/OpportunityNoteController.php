<?php

namespace App\Domain\Worldwide\Controllers\V1\Opportunity;

use App\Domain\Note\Models\Note;
use App\Domain\Note\Queries\NoteQueries;
use App\Domain\Note\Resources\V1\OpportunityNoteListResource;
use App\Domain\Note\Resources\V1\OpportunityNoteWithIncludesResource;
use App\Domain\Note\Services\NoteEntityService;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Requests\Opportunity\CreateOpportunityNoteRequest;
use App\Domain\Worldwide\Requests\Opportunity\UpdateOpportunityNoteRequest;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class OpportunityNoteController extends Controller
{
    /**
     * Paginate opportunity notes.
     *
     * @throws AuthorizationException
     */
    public function paginateOpportunityNotes(Request $request,
                                             NoteQueries $queries,
                                             Opportunity $opportunity): AnonymousResourceCollection
    {
        $this->authorize('view', $opportunity);
        $this->authorize('viewAny', Note::class);

        $paginator = $queries->listNotesOfModelQuery($opportunity, $request)->apiPaginate();

        return OpportunityNoteListResource::collection($paginator);
    }

    /**
     * Show opportunity note.
     *
     * @throws AuthorizationException
     */
    public function showOpportunityNote(Request $request, Note $note): OpportunityNoteWithIncludesResource
    {
        $this->authorize('view', $note);

        return OpportunityNoteWithIncludesResource::make($note);
    }

    /**
     * Create opportunity note.
     *
     * @throws AuthorizationException
     */
    public function storeOpportunityNote(CreateOpportunityNoteRequest $request,
                                         NoteEntityService $entityService,
                                         Opportunity $opportunity): OpportunityNoteWithIncludesResource
    {
        $this->authorize('create', Note::class);

        $note = $entityService
            ->setCauser($request->user())
            ->createNoteForModel($request->getCreateNoteData(), $opportunity);

        return OpportunityNoteWithIncludesResource::make($note);
    }

    /**
     * Update opportunity note.
     *
     * @throws AuthorizationException
     */
    public function updateOpportunityNote(UpdateOpportunityNoteRequest $request,
                                          NoteEntityService $entityService,
                                          Note $note): OpportunityNoteWithIncludesResource
    {
        $this->authorize('update', $note);

        $entityService
            ->setCauser($request->user())
            ->updateNote($note, $request->getUpdateNoteData());

        return OpportunityNoteWithIncludesResource::make($note);
    }

    /**
     * Delete opportunity note.
     *
     * @throws AuthorizationException
     */
    public function deleteOpportunityNote(Request $request,
                                          NoteEntityService $entityService,
                                          Note $note): Response
    {
        $this->authorize('delete', $note);

        $entityService
            ->setCauser($request->user())
            ->deleteNote($note);

        return response()->noContent();
    }
}
