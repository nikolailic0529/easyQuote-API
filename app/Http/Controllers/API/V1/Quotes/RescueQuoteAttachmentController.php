<?php

namespace App\Http\Controllers\API\V1\Quotes;

use App\DTO\Attachment\CreateAttachmentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attachment\CreateAttachment;
use App\Http\Resources\V1\Attachment\AttachmentOfQuote;
use App\Models\Attachment;
use App\Models\Quote\Quote;
use App\Services\Attachment\AttachmentEntityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RescueQuoteAttachmentController extends Controller
{
    /**
     * Show a list of existing attachments of the quote entity.
     *
     * @param  Quote  $quote
     * @return AnonymousResourceCollection
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
     * @param  CreateAttachment  $request
     * @param  AttachmentEntityService  $entityService
     * @param  Quote  $quote
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeAttachmentForQuote(
        CreateAttachment $request,
        AttachmentEntityService $entityService,
        Quote $quote
    ): JsonResponse {
        $this->authorize('view', $quote);
        $this->authorize('create', Attachment::class);

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
     * @param  AttachmentEntityService  $entityService
     * @param  Attachment  $attachment
     * @param  Quote  $quote
     * @return JsonResponse
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