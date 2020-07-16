<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class ImportResponse implements Responsable
{
    public $result;

    public ?string $error;

    public function __construct($result, $error = null)
    {
        $this->result = $result;
        $this->error = $error;
    }

    /**
     * Determine whether import response is failed.
     *
     * @return boolean
     */
    public function failed(): bool
    {
        return ! is_null($this->error);
    }

    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $data = ['result' => $this->result];

        $status = is_null($this->error) ? JsonResponse::HTTP_OK : JsonResponse::HTTP_UNPROCESSABLE_ENTITY;

        transform($this->error, function () use (&$data) {
            $data += ['message' => $this->error];
        });

        return response()->json($data, $status);
    }
}
