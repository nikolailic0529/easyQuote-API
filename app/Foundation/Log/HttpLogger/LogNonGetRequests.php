<?php

namespace App\Foundation\Log\HttpLogger;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Spatie\HttpLogger\LogProfile;

class LogNonGetRequests implements LogProfile
{
    public function __construct(protected Config $config)
    {
    }

    public function shouldLogRequest(Request $request): bool
    {
        $enforceLogFor = $this->config->get('http-logger.enforce_request_log', []);

        if ($request->is($enforceLogFor)) {
            return true;
        }

        return in_array(strtolower($request->method()), ['post', 'put', 'patch', 'delete']);
    }
}
