<?php

namespace App\Http\Controllers\API\V1\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyNote\CreateCompanyNote;
use App\Http\Requests\CompanyNote\UpdateCompanyNote;
use App\Models\Company;
use App\Models\Note\Note;
use App\Services\Note\NoteEntityService;
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
     * @param CreateCompanyNote $request
     * @param NoteEntityService $entityService
     * @param Company $company
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeCompanyNote(CreateCompanyNote $request,
                                     NoteEntityService $entityService,
                                     Company           $company): JsonResponse
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
     * @param Note $note
     * @return JsonResponse
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
     * @param UpdateCompanyNote $request
     * @param NoteEntityService $entityService
     * @param Note $note
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateCompanyNote(UpdateCompanyNote $request,
                                      NoteEntityService $entityService,
                                      Note              $note): JsonResponse
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
     * @param Request $request
     * @param NoteEntityService $entityService
     * @param Note $note
     * @return Response
     * @throws AuthorizationException
     */
    public function deleteCompanyNote(Request           $request,
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