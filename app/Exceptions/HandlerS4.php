<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Container\Container;

class HandlerS4 extends Handler
{
    /** @var \App\Contracts\Services\ResponseInterface */
    protected $response;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->response = $container->make('response.service');
    }

    /**
     * Convert a validation exception into a JSON response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Validation\ValidationException  $exception
     * @return \Illuminate\Http\JsonResponse
     */
    protected function invalidJson($request, ValidationException $exception)
    {
        report_logger(['ErrorCode' => 'EQ_INV_DP_01'], $exception->errors());

        return $this->response->invalidJson($request, $exception);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $this->response->makeErrorResponse(EQ_UA_01, 'EQ_UA_01', 401);
    }
}
