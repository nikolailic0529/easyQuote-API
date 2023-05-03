<?php

namespace App\Domain\Rescue\Controllers\V1;

use App\Domain\Attachment\DataTransferObjects\CreateAttachmentData;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Attachment\Requests\CreateAttachmentRequest;
use App\Domain\Attachment\Resources\V1\AttachmentOfQuote;
use App\Domain\Attachment\Services\AttachmentEntityService;
use App\Domain\Rescue\Models\Quote;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RescueQuoteAttachmentController extends Controller
{
    /**
     * Show a list of existing attachments of the quote entity.
     *
     * @throws AuthorizationException
     */
    public function showAttachmentsOfQuote(Quote $quote): AnonymousResourceCollection
    {
        $this->authorize('view', $quote);
        $this->authorize('viewAny', Attachment::class);

        $collection = $quote->attachments()->latest()->get();

        return AttachmentOfQuote::collection($collection);
    }

    /**
     * Store a new attachment for the quote entity.
     *
     * @throws AuthorizationException
     */
    public function storeAttachmentForQuote(
        CreateAttachmentRequest $request,
        AttachmentEntityService $entityService,
        Quote $quote
    ): JsonResponse {
        $this->authorize('view', $quote);
        $this->authorize('create', Attachment::class);

        $request->validateFileHash($quote);

        $resource = $entityService
            ->setCauser($request->user())
            ->createAttachmentForEntity(
                data: CreateAttachmentData::from($request),
                entity: $quote,
            );

        return response()->json(
            data: AttachmentOfQuote::make($resource),
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * Delete the specified attachment of the quote entity.
     *
     * @throws AuthorizationException
     */
    public function deleteAttachmentOfQuote(
        AttachmentEntityService $entityService,
        Quote $quote,
        Attachment $attachment
    ): JsonResponse {
        $this->authorize('view', $quote);
        $this->authorize('delete', $attachment);

        $entityService->deleteAttachment($attachment, $quote);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
