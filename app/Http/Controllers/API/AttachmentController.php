<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attachment\CreateAttachmentRequest;
use App\Http\Resources\AttachmentResource;
use App\Services\AttachmentService as Service;
use App\Models\Attachment;
use Illuminate\Http\Request;

class AttachmentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param CreateAttachmentRequest $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(CreateAttachmentRequest $request, Service $service)
    {
        return response()->json(
            AttachmentResource::make(
                $service->store($request->validated())
            )
        );
    }
}
