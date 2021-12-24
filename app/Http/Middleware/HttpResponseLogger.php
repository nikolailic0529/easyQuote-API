<?php

namespace App\Http\Middleware;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

final class HttpResponseLogger
{
    public function __construct(protected LoggerInterface $logger,
                                protected Config          $config)
    {
    }

    public function handle(Request $request, \Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response)
    {
        $status = $response->getStatusCode();

        $content = $response->getContent();

        $this->logger->info("RESPONSE: $status - $content");
    }
}