<?php

namespace App\Domain\DocumentEngine\Controllers\V1;

use App\Domain\DocumentEngine\DocumentEngineEventService;
use App\Domain\DocumentEngine\Requests\NewEventRequest;
use App\Foundation\Http\Controller;
use App\Foundation\Http\Middleware\CheckClientCredentials;
use Illuminate\Contracts\Support\Responsable;

class DocumentEngineEventController extends Controller
{
    public function __construct()
    {
        $this->middleware(CheckClientCredentials::class);
    }

    /**
     * Process the incoming event payload from document engine api.
     */
    public function handleDocumentEngineEvent(NewEventRequest $request,
                                              DocumentEngineEventService $eventService): Responsable
    {
        return $eventService
            ->setCauser($request->user())
            ->processEvent($request->getEventData());
    }
}
