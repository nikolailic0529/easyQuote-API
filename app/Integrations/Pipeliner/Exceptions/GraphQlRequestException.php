<?php

namespace App\Integrations\Pipeliner\Exceptions;

use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\Response;

class GraphQlRequestException extends HttpClientException implements PipelinerIntegrationException
{
    public readonly Response $response;

    public readonly array $errors;

    /**
     * Create a new exception instance.
     *
     * @param \Illuminate\Http\Client\Response $response
     * @return void
     */
    public function __construct(Response $response)
    {
        parent::__construct($this->prepareMessage($response), $response->status());

        $this->response = $response;
        $this->errors = $response->json('errors', []);
    }

    /**
     * @throws GraphQlRequestException
     */
    public static function throwIfHasErrors(Response $response): void
    {
        if (null !== $response->json('errors')) {

            throw new static($response);
        }
    }

    /**
     * Prepare the exception message.
     *
     * @param \Illuminate\Http\Client\Response $response
     * @return string
     */
    protected function prepareMessage(Response $response)
    {
        $message = "HTTP request returned status code {$response->status()}";

        $errors = $response->json('errors');

        if (is_array($errors)) {
            return collect($errors)->implode('message');
        }

        return $message;
    }
}