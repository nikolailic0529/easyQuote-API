<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attachment\CreateAttachment;
use App\Http\Resources\V1\Attachment\CreatedAttachment;
use App\Models\Attachment;
use App\Services\Attachment\AttachmentEntityService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param CreateAttachment $request
     * @param \App\Services\Attachment\AttachmentEntityService $service
     * @return JsonResponse
     */
    public function __invoke(CreateAttachment        $request,
                             AttachmentEntityService $service): JsonResponse
    {
        $resource = $service->createAttachmentFromUploadedFile(
            file: $request->getUploadedFile(),
            attachmentType: $request->getAttachmentType(),
        );

        return response()->json(
            CreatedAttachment::make($resource)
        );
    }

    public function downloadAttachment(Attachment              $attachment,
                                       AttachmentEntityService $service): StreamedResponse
    {
        return $service->downloadAttachment($attachment);
    }
}
