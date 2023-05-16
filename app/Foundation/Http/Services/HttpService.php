<?php

namespace App\Foundation\Http\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class HttpService implements \App\Foundation\Http\Contracts\HttpInterface
{
    protected array $invalidRequestExceptions = [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class,
    ];

    public function makeErrorResponse(string $details, string $code, int $status, array $headers = []): JsonResponse
    {
        $data = $this->convertErrorToArray($details, $code);

        return new JsonResponse($data, $status, $headers);
    }

    public function invalidJson(Request $request, ValidationException $e): JsonResponse
    {
        return new JsonResponse([
            'ErrorUrl' => $request->fullUrl(),
            'ErrorCode' => 'EQ_INV_DP_01',
            'Error' => [
                'headers' => Arr::except($request->headers->all(), ['authorization']),
                'original' => $e->errors(),
                'exception' => get_class($e),
            ],
            'ErrorDetails' => EQ_INV_DP_01,
            'message' => optional($e->validator->errors())->first(),
        ], $e->status);
    }

    public function isInvalidRequestException(\Throwable $exception): bool
    {
        return isset(array_flip($this->invalidRequestExceptions)[get_class($exception)]);
    }

    public function convertErrorToArray(string $details, string $code): array
    {
        return [
            'ErrorCode' => $code,
            'ErrorDetails' => $details,
            'message' => $details,
        ];
    }
}
