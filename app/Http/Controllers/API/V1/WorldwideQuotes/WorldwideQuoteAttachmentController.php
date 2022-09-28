<?php

namespace App\Http\Controllers\API\V1\WorldwideQuotes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attachment\CreateAttachment;
use App\Http\Resources\V1\Attachment\AttachmentOfQuote;
use App\Models\Attachment;
use App\Models\Quote\WorldwideQuote;
use App\Services\Attachment\AttachmentEntityService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WorldwideQuoteAttachmentController extends Controller
{
    /**
     * Show a list of existing attachments of the worldwide quote entity.
     *
     * @param WorldwideQuote $worldwideQuote
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function showAttachmentsOfWorldwideQuote(WorldwideQuote $worldwideQuote): AnonymousResourceCollection
    {
        $this->authorize('view', $worldwideQuote);

        $collection = $worldwideQuote->attachments()->get();

        return AttachmentOfQuote::collection($collection);
    }

    /**
     * Store a new attachment for the worldwide quote entity.
     *
     * @param CreateAttachment $request
     * @param AttachmentEntityService $entityService
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function storeAttachmentForWorldwideQuote(CreateAttachment        $request,
                                                     AttachmentEntityService $entityService,
                                                     WorldwideQuote          $worldwideQuote): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        $resource = $entityService->createAttachmentForEntity(
            file: $request->getUploadedFile(),
            type: $request->getAttachmentType(),
            entity: $worldwideQuote,
        );

        return response()->json(
            data: AttachmentOfQuote::make($resource),
            status: Response::HTTP_CREATED,
        );

    }

    /**
     * Delete the specified attachment of the quote entity.
     *
     * @param AttachmentEntityService $entityService
     * @param WorldwideQuote $worldwideQuote
     * @param Attachment $attachment
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function deleteAttachmentOfQuote(AttachmentEntityService $entityService,
                                            WorldwideQuote          $worldwideQuote,
                                            Attachment              $attachment): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        $entityService->deleteAttachment($attachment, $worldwideQuote);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}