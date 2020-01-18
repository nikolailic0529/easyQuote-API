<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Container\Container;
use Exception;

class HandlerS4 extends Handler
{
    /** @var \App\Contracts\Services\ResponseInterface */
    protected $responseService;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->responseService = $container->make('response.service');
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

        return $this->responseService->invalidJson($request, $exception);
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
        return $this->responseService->make(EQ_UA_01, 'EQ_UA_01', 401);
    }

    /**
     * Convert the given exception to an array.
     *
     * @param  \Exception  $e
     * @return array
     */
    protected function convertExceptionToArray(Exception $e)
    {
        return $this->responseService->prepareData(
            $this->isHttpException($e) ? EQ_INV_REQ_01 : EQ_SE_01,
            $this->isHttpException($e) ? 'EQ_INV_REQ_01' : 'EQ_SE_01'
        );
    }
}
