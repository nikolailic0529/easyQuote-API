<?php

namespace App\Http\Controllers\API\V1\Pipeliner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pipeliner\HandleWebhookEvent;
use App\Services\Pipeliner\Webhook\WebhookEventService;
use Illuminate\Http\Response;

class PipelinerWebhookController extends Controller
{
    /**
     * Handle webhook event.
     *
     * @param HandleWebhookEvent $request
     * @param WebhookEventService $service
     * @return Response
     */
    public function handleWebhookEvent(HandleWebhookEvent $request, WebhookEventService $service): Response
    {
        $service->handle($request->getIncomingWebhookData());

        return response()->noContent();
    }
}
