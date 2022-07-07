<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OpportunityNote\CreateOpportunityNote;
use App\Http\Requests\OpportunityNote\UpdateOpportunityNote;
use App\Http\Resources\V1\OpportunityNote\OpportunityNoteListResource;
use App\Http\Resources\V1\OpportunityNote\OpportunityNoteWithIncludesResource;
use App\Models\Note\Note;
use App\Models\Opportunity;
use App\Queries\NoteQueries;
use App\Services\Note\NoteEntityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class OpportunityNoteController extends Controller
{
    /**
     * Paginate opportunity notes.
     *
     * @param Request $request
     * @param NoteQueries $queries
     * @param Opportunity $opportunity
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function paginateOpportunityNotes(Request     $request,
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
     * @param CreateOpportunityNote $request
     * @param NoteEntityService $entityService
     * @param Opportunity $opportunity
     * @return \App\Http\Resources\V1\OpportunityNote\OpportunityNoteWithIncludesResource
     * @throws AuthorizationException
     */
    public function storeOpportunityNote(CreateOpportunityNote $request,
                                         NoteEntityService     $entityService,
                                         Opportunity           $opportunity): OpportunityNoteWithIncludesResource
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
     * @param UpdateOpportunityNote $request
     * @param NoteEntityService $entityService
     * @param Note $note
     * @return \App\Http\Resources\V1\OpportunityNote\OpportunityNoteWithIncludesResource
     * @throws AuthorizationException
     */
    public function updateOpportunityNote(UpdateOpportunityNote $request,
                                          NoteEntityService     $entityService,
                                          Note                  $note): OpportunityNoteWithIncludesResource
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
     * @param Request $request
     * @param NoteEntityService $entityService
     * @param Note $note
     * @return Response
     * @throws AuthorizationException
     */
    public function deleteOpportunityNote(Request           $request,
                                          NoteEntityService $entityService,
                                          Note              $note): Response
    {
        $this->authorize('delete', $note);

        $entityService
            ->setCauser($request->user())
            ->deleteNote($note);

        return response()->noContent();
    }
}