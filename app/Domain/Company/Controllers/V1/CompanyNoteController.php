<?php

namespace App\Domain\Company\Controllers\V1;

use App\Domain\Company\Models\Company;
use App\Domain\Company\Requests\CreateCompanyNote;
use App\Domain\Company\Requests\UpdateCompanyNote;
use App\Domain\Note\Models\Note;
use App\Domain\Note\Services\NoteEntityService;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class CompanyNoteController extends Controller
{
    /**
     * Store a new company note entity.
     *
     * @throws AuthorizationException
     */
    public function storeCompanyNote(CreateCompanyNote $request,
                                     NoteEntityService $entityService,
                                     Company $company): JsonResponse
    {
        $this->authorize('view', $company);
        $this->authorize('create', Note::class);

        $note = $entityService
            ->setCauser($request->user())
            ->createNoteForModel($request->getCreateNoteData(), $company);

        return response()->json(
            $note,
            BaseResponse::HTTP_CREATED,
        );
    }

    /**
     * Show note of company.
     *
     * @throws AuthorizationException
     */
    public function showCompanyNote(Note $note): JsonResponse
    {
        $this->authorize('view', $note);

        return response()->json(
            $note,
            BaseResponse::HTTP_OK
        );
    }

    /**
     * Update note of company.
     *
     * @throws AuthorizationException
     */
    public function updateCompanyNote(UpdateCompanyNote $request,
                                      NoteEntityService $entityService,
                                      Note $note): JsonResponse
    {
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
     * Delete note of company.
     *
     * @throws AuthorizationException
     */
    public function deleteCompanyNote(Request $request,
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
