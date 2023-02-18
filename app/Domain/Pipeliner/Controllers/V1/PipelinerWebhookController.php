<?php

namespace App\Domain\Pipeliner\Controllers\V1;

use App\Domain\Pipeliner\Requests\HandleWebhookEventRequest;
use App\Domain\Pipeliner\Services\Webhook\WebhookEventService;
use App\Foundation\Http\Controller;
use Illuminate\Http\Response;

class PipelinerWebhookController extends Controller
{
    /**
     * Handle webhook event.
     */
    public function handleWebhookEvent(HandleWebhookEventRequest $request, WebhookEventService $service): Response
    {
        $service->handle($request->getIncomingWebhookData());

        return response()->noContent();
    }
}
