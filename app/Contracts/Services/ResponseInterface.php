<?php

namespace App\Contracts\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

interface ResponseInterface
{
    /**
     * Determine that the current request is related to S4.
     *
     * @return boolean
     */
    public function isS4(): bool;

    /**
     * Make a new Json response instance.
     *
     * @param string $details
     * @param string $code
     * @param integer $status
     * @return \Illuminate\Http\JsonResponse
     */
    public function makeErrorResponse(string $details, string $code, int $status): JsonResponse;

    /**
     * Determine if the given exception is related to invalid request exceptions.
     *
     * @param Throwable $exception
     * @return boolean
     */
    public function isInvalidRequestException(Throwable $exception): bool;

    /**
     * Convert a validation exception into a JSON response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Validation\ValidationException  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function invalidJson(Request $request, ValidationException $e): JsonResponse;

    /**
     * Prepare Response data.
     *
     * @param string $details
     * @param string $code
     * @param boolean $s4Format
     * @return array
     */
    public function errorToArray(string $details, string $code, bool $s4Format = false): array;
}
