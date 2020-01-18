<?php

namespace App\Services;

use App\Contracts\Services\ResponseInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class Response implements ResponseInterface
{
    public function isS4(): bool
    {
        return app('request')->is('api/s4/*');
    }

    public function make(string $details, string $code, int $status): JsonResponse
    {
        $data = $this->prepareData($details, $code);

        return new JsonResponse($data, $status);
    }

    public function invalidJson(Request $request, ValidationException $e): JsonResponse
    {
        return new JsonResponse([
            'ErrorUrl' => $request->fullUrl(),
            'ErrorCode' => 'EQ_INV_DP_01',
            'Error' => [
                'headers' => $request->headers->all(),
                'original' => $e->errors(),
                'exception' => get_class($e)
            ],
            'ErrorDetails' => EQ_INV_DP_01
        ], $e->status);
    }

    public function prepareData(string $details, string $code): array
    {
        if ($this->isS4()) {
            return [
                'ErrorCode' => $code,
                'ErrorDetails' => $details
            ];
        }

        return [
            'error_code' => $code,
            'message' => $details
        ];
    }
}
