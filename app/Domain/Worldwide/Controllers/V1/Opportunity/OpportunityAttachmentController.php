<?php

namespace App\Domain\Worldwide\Controllers\V1\Opportunity;

use App\Domain\Attachment\DataTransferObjects\CreateAttachmentData;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Attachment\Requests\CreateAttachmentRequest;
use App\Domain\Attachment\Resources\V1\OpportunityAttachmentResource;
use App\Domain\Attachment\Services\AttachmentEntityService;
use App\Domain\Worldwide\Models\Opportunity;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class OpportunityAttachmentController extends Controller
{
    /**
     * List opportunity attachments.
     *
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
     * @throws AuthorizationException
     */
    public function storeAttachment(
        CreateAttachmentRequest $request,
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
