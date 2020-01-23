<?php

namespace App\Services;

use App\Contracts\Services\HttpInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class HttpService implements HttpInterface
{
    protected $invalidRequestExceptions = [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class
    ];

    public function makeErrorResponse(string $details, string $code, int $status): JsonResponse
    {
        $data = $this->convertErrorToArray($details, $code);

        return new JsonResponse($data, $status);
    }

    public function invalidJson(Request $request, ValidationException $exception): JsonResponse
    {
        return new JsonResponse([
            'ErrorUrl' => $request->fullUrl(),
            'ErrorCode' => 'EQ_INV_DP_01',
            'Error' => [
                'headers' => $request->headers->all(),
                'original' => $exception->errors(),
                'exception' => get_class($exception)
            ],
            'ErrorDetails' => EQ_INV_DP_01,
            'message' => optional($exception->validator->errors())->first()
        ], $exception->status);
    }

    public function isInvalidRequestException(Throwable $exception): bool
    {
        return isset(array_flip($this->invalidRequestExceptions)[get_class($exception)]);
    }

    public function convertErrorToArray(string $details, string $code): array
    {
        return [
            'ErrorCode' => $code,
            'ErrorDetails' => $details,
            'message' => $details
        ];
    }
}
