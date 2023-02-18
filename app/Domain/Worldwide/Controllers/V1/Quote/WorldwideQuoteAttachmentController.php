<?php

namespace App\Domain\Worldwide\Controllers\V1\Quote;

use App\Domain\Attachment\DataTransferObjects\CreateAttachmentData;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Attachment\Requests\CreateAttachmentRequest;
use App\Domain\Attachment\Resources\V1\AttachmentOfQuote;
use App\Domain\Attachment\Services\AttachmentEntityService;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WorldwideQuoteAttachmentController extends Controller
{
    /**
     * Show a list of existing attachments of the worldwide quote entity.
     *
     * @throws AuthorizationException
     */
    public function showAttachmentsOfWorldwideQuote(WorldwideQuote $worldwideQuote): AnonymousResourceCollection
    {
        $this->authorize('view', $worldwideQuote);
        $this->authorize('viewAny', Attachment::class);

        $collection = $worldwideQuote->attachments()->latest()->get();

        return AttachmentOfQuote::collection($collection);
    }

    /**
     * Store a new attachment for the worldwide quote entity.
     *
     * @throws AuthorizationException
     */
    public function storeAttachmentForWorldwideQuote(
        CreateAttachmentRequest $request,
        AttachmentEntityService $entityService,
        WorldwideQuote $worldwideQuote
    ): JsonResponse {
        $this->authorize('view', $worldwideQuote);
        $this->authorize('create', Attachment::class);

        $resource = $entityService
            ->setCauser($request->user())
            ->createAttachmentForEntity(
                data: CreateAttachmentData::from($request),
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
     * @throws AuthorizationException
     */
    public function deleteAttachmentOfQuote(
        AttachmentEntityService $entityService,
        WorldwideQuote $worldwideQuote,
        Attachment $attachment
    ): JsonResponse {
        $this->authorize('view', $worldwideQuote);
        $this->authorize('delete', $attachment);

        $entityService->deleteAttachment($attachment, $worldwideQuote);

        return response()->json(status: Response::HTTP_NO_CONTENT);
    }
}
