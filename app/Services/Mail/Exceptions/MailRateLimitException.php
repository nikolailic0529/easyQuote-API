<?php

namespace App\Services\Mail\Exceptions;

use Carbon\CarbonInterval;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MailRateLimitException extends \Exception
{
    public static function limitExceeded(int $maxAttempts, int $availableIn): static
    {
        $availableInForHumans = CarbonInterval::seconds($availableIn)->ceilMinutes()->forHumans();

        return new static("Mail limit exceeded, max: [$maxAttempts], available in: [$availableInForHumans]");
    }

    public function render(Request $request): Response
    {
        return response()->json(['message' => 'Mail limit exceeded.'], Response::HTTP_TOO_MANY_REQUESTS);
    }
}