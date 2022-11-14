<?php

namespace App\Services\Elasticsearch\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QueueRebuildSearchException extends Exception
{
    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->message], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}