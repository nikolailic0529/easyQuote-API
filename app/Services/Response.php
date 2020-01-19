<?php

namespace App\Services;

use App\Contracts\Services\ResponseInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class Response implements ResponseInterface
{
    protected $invalidRequestExceptions = [
        \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
        \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException::class
    ];

    public function isS4(): bool
    {
        return app('request')->is('api/s4/*');
    }

    public function makeErrorResponse(string $details, string $code, int $status): JsonResponse
    {
        $data = $this->errorToArray($details, $code);

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

    public function isInvalidRequestException(Throwable $exception): bool
    {
        return isset(array_flip($this->invalidRequestExceptions)[get_class($exception)]);
    }

    public function errorToArray(string $details, string $code, bool $s4Format = false): array
    {
        if ($s4Format || $this->isS4()) {
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
