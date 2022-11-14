<?php

namespace App\Http\Controllers\API\V1;

use App\DTO\Attachment\CreateAttachmentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attachment\CreateAttachment;
use App\Http\Resources\V1\Attachment\OpportunityAttachmentResource;
use App\Models\Attachment;
use App\Models\Opportunity;
use App\Services\Attachment\AttachmentEntityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class OpportunityAttachmentController extends Controller
{
    /**
     * List opportunity attachments.
     *
     * @param  Opportunity  $opportunity
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function listAttachments(Opportunity $opportunity): AnonymousResourceCollection
    {
        $this->authorize('view', $opportunity);
        $this->authorize('viewAny', Attachment::class);

        $collection = $opportunity->attachments()->latest()->get();

        return OpportunityAttachmentResource::collection($collection);
    }

    /**
     * Create opportunity attachment.
     *
     * @param  CreateAttachment  $request
     * @param  AttachmentEntityService  $entityService
     * @param  Opportunity  $opportunity
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeAttachment(
        CreateAttachment $request,
        AttachmentEntityService $entityService,
        Opportunity $opportunity
    ): JsonResponse {
        $this->authorize('view', $opportunity);
        $this->authorize('create', Attachment::class);

        $resource = $entityService
            ->setCauser($request->user())
            ->createAttachmentForEntity(
                data: CreateAttachmentData::from($request),
                entity: $opportunity,
            );

        return response()->json(
            data: OpportunityAttachmentResource::make($resource),
            status: Response::HTTP_CREATED,
        );

    }

    /**
     * Delete the specified attachment of the quote entity.
     *
     * @param  AttachmentEntityService  $entityService
     * @param  Opportunity  $opportunity
     * @param  Attachment  $attachment
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function deleteAttachment(
        AttachmentEntityService $entityService,
        Opportunity $opportunity,
        Attachment $attachment
    ): JsonResponse {
        $this->authorize('view', $opportunity);
        $this->authorize('delete', $attachment);

        $entityService->deleteAttachment($attachment, $opportunity);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}