<?php

namespace App\Http\Controllers\API\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyNote\CreateCompanyNote;
use App\Http\Requests\CompanyNote\UpdateCompanyNote;
use App\Models\Company;
use App\Models\CompanyNote;
use App\Services\Company\CompanyNoteEntityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CompanyNoteController extends Controller
{

    /**
     * Store a new company note entity.
     *
     * @param CreateCompanyNote $request
     * @param CompanyNoteEntityService $entityService
     * @param Company $company
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeCompanyNote(CreateCompanyNote $request,
                                     CompanyNoteEntityService $entityService,
                                     Company $company): JsonResponse
    {
        $this->authorize('view', $company);
        $this->authorize('create', CompanyNote::class);

        return response()->json(
            $entityService->createCompanyNote(noteText: $request->getNoteText(), company: $company, user: $request->user()),
            Response::HTTP_CREATED,
        );
    }

    /**
     * Show the specified company note entity.
     *
     * @param CompanyNote $companyNote
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function showCompanyNote(CompanyNote $companyNote): JsonResponse
    {
        $this->authorize('view', $companyNote->company);
        $this->authorize('view', $companyNote);

        return response()->json(
            $companyNote,
            Response::HTTP_OK
        );
    }

    /**
     * Update the specified company note entity.
     *
     * @param UpdateCompanyNote $request
     * @param CompanyNoteEntityService $entityService
     * @param CompanyNote $companyNote
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateCompanyNote(UpdateCompanyNote $request,
                                      CompanyNoteEntityService $entityService,
                                      CompanyNote $companyNote)
    {
        $this->authorize('view', $companyNote->company);
        $this->authorize('update', $companyNote);

        return response()->json(
            $entityService->updateCompanyNote($companyNote, $request->getNoteText()),
            Response::HTTP_OK
        );
    }

    /**
     * Delete the specified company note entity.
     *
     * @param Request $request
     * @param CompanyNoteEntityService $entityService
     * @param CompanyNote $companyNote
     * @throws AuthorizationException
     */
    public function deleteCompanyNote(Request $request,
                                      CompanyNoteEntityService $entityService,
                                      CompanyNote $companyNote): JsonResponse
    {
        $this->authorize('view', $companyNote->company);
        $this->authorize('delete', $companyNote);

        $entityService->deleteCompanyNote($companyNote);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }

}