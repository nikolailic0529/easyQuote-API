<?php

namespace App\Foundation\Http\Contracts;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

interface HttpInterface
{
    /**
     * Make a new Json response instance.
     */
    public function makeErrorResponse(string $details, string $code, int $status, array $headers = []): JsonResponse;

    /**
     * Determine if the given exception is related to invalid request exceptions.
     */
    public function isInvalidRequestException(\Throwable $exception): bool;

    /**
     * Convert a validation exception into a JSON response.
     *
     * @param \Illuminate\Validation\ValidationException $exception
     */
    public function invalidJson(Request $request, ValidationException $e): JsonResponse;

    /**
     * Convert the given details and code to an error array.
     */
    public function convertErrorToArray(string $details, string $code): array;
}
