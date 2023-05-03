<?php

namespace App\Domain\Note\Controllers;

use App\Domain\Company\Models\Company;
use App\Domain\Note\DataTransferObjects\CreateNoteData;
use App\Domain\Note\DataTransferObjects\UpdateNoteData;
use App\Domain\Note\Models\Note;
use App\Domain\Note\Queries\NoteQueries;
use App\Domain\Note\Queries\UnifiedNoteQueries;
use App\Domain\Note\Resources\V1\NoteCollection;
use App\Domain\Note\Resources\V1\NoteResource;
use App\Domain\Note\Services\NoteEntityService;
use App\Domain\Rescue\Models\Quote;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NoteController extends Controller
{
    /**
     * Paginate notes of company.
     *
     * @throws AuthorizationException
     */
    public function paginateCompanyNotes(
        Request $request,
        UnifiedNoteQueries $queries,
        Company $related
    ): NoteCollection {
        $this->authorize('view', $related);
        $this->authorize('viewAny', Note::class);

        $paginator = $queries->simpleListOfCompanyNotesQuery($related, $request)->apiPaginate();

        return NoteCollection::make($paginator);
    }

    /**
     * Paginate notes of rescue quote.
     *
     * @throws AuthorizationException
     */
    public function paginateRescueQuoteNotes(
        Request $request,
        NoteQueries $queries,
        Quote $related
    ): NoteCollection {
        $this->authorize('view', $related);
        $this->authorize('viewAny', Note::class);

        $paginator = $queries->listNotesOfModelQuery($related, $request)->apiPaginate();

        return NoteCollection::make($paginator);
    }

    /**
     * Paginate notes of worldwide quote.
     *
     * @throws AuthorizationException
     */
    public function paginateWorldwideQuoteNotes(
        Request $request,
        NoteQueries $queries,
        WorldwideQuote $related
    ): NoteCollection {
        $this->authorize('view', $related);
        $this->authorize('viewAny', Note::class);

        $paginator = $queries->listNotesOfModelQuery($related, $request)->apiPaginate();

        return NoteCollection::make($paginator);
    }

    /**
     * Paginate notes of opportunity.
     *
     * @throws AuthorizationException
     */
    public function paginateOpportunityNotes(
        Request $request,
        NoteQueries $queries,
        Opportunity $related
    ): NoteCollection {
        $this->authorize('view', $related);
        $this->authorize('viewAny', Note::class);

        $paginator = $queries->listNotesOfModelQuery($related, $request)->apiPaginate();

        return NoteCollection::make($paginator);
    }

    /**
     * Create note for company.
     *
     * @throws AuthorizationException
     */
    public function storeCompanyNote(
        Request $request,
        CreateNoteData $data,
        NoteEntityService $entityService,
        Company $related
    ): NoteResource {
        $this->authorize('view', $related);
        $this->authorize('create', Note::class);

        $note = $entityService
            ->setCauser($request->user())
            ->createNoteForModel($data, $related);

        return NoteResource::make($note);
    }

    /**
     * Create note for rescue quote.
     *
     * @throws AuthorizationException
     */
    public function storeRescueQuoteNote(
        Request $request,
        CreateNoteData $data,
        NoteEntityService $entityService,
        Quote $related
    ): NoteResource {
        $this->authorize('view', $related);
        $this->authorize('create', Note::class);

        $note = $entityService
            ->setCauser($request->user())
            ->createNoteForModel($data, $related);

        return NoteResource::make($note);
    }

    /**
     * Create note for worldwide quote.
     *
     * @throws AuthorizationException
     */
    public function storeWorldwideQuoteNote(
        Request $request,
        CreateNoteData $data,
        NoteEntityService $entityService,
        WorldwideQuote $related
    ): NoteResource {
        $this->authorize('view', $related);
        $this->authorize('create', Note::class);

        $note = $entityService
            ->setCauser($request->user())
            ->createNoteForModel($data, $related);

        return NoteResource::make($note);
    }

    /**
     * Create note for opportunity.
     *
     * @throws AuthorizationException
     */
    public function storeOpportunityNote(
        Request $request,
        CreateNoteData $data,
        NoteEntityService $entityService,
        Opportunity $related
    ): NoteResource {
        $this->authorize('view', $related);
        $this->authorize('create', Note::class);

        $note = $entityService
            ->setCauser($request->user())
            ->createNoteForModel($data, $related);

        return NoteResource::make($note);
    }

    /**
     * Show note.
     *
     * @throws AuthorizationException
     */
    public function showNote(Note $note): NoteResource
    {
        $this->authorize('view', $note);

        return NoteResource::make($note);
    }

    /**
     * Update note.
     *
     * @throws AuthorizationException
     */
    public function updateNote(
        Request $request,
        UpdateNoteData $data,
        NoteEntityService $entityService,
        Note $note
    ): NoteResource {
        $this->authorize('update', $note);

        $note = $entityService
            ->setCauser($request->user())
            ->updateNote($note, $data);

        return NoteResource::make($note);
    }

    /**
     * Delete note.
     *
     * @throws AuthorizationException
     */
    public function deleteNote(
        Request $request,
        NoteEntityService $entityService,
        Note $note
    ): Response {
        $this->authorize('delete', $note);

        $entityService
            ->setCauser($request->user())
            ->deleteNote($note);

        return response()->noContent();
    }
}
