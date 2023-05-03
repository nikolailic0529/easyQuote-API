<?php

namespace App\Domain\Attachment\Controllers\V1;

use App\Domain\Attachment\DataTransferObjects\CreateAttachmentData;
use App\Domain\Attachment\Models\Attachment;
use App\Domain\Attachment\Requests\CreateAttachmentRequest;
use App\Domain\Attachment\Resources\V1\CreatedAttachment;
use App\Domain\Attachment\Services\AttachmentEntityService;
use App\Foundation\Http\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function __invoke(
        CreateAttachmentRequest $request,
        AttachmentEntityService $service
    ): JsonResponse {
        $resource = $service
            ->setCauser($request->user())
            ->createAttachmentFromFile(
                CreateAttachmentData::from($request)
            );

        return response()->json(
            CreatedAttachment::make($resource)
        );
    }

    public function downloadAttachment(
        Attachment $attachment,
        AttachmentEntityService $service
    ): StreamedResponse {
        return $service->downloadAttachment($attachment);
    }
}
