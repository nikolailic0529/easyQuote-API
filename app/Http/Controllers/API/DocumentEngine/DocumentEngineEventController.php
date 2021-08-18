<?php

namespace App\Http\Controllers\API\DocumentEngine;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckClientCredentials;
use App\Http\Requests\DocumentEngine\NewEvent;
use App\Services\DocumentEngine\DocumentEngineEventService;
use Illuminate\Contracts\Support\Responsable;

class DocumentEngineEventController extends Controller
{
    public function __construct()
    {
        $this->middleware(CheckClientCredentials::class);
    }

    /**
     * Process the incoming event payload from document engine api.
     *
     * @param NewEvent $request
     * @param DocumentEngineEventService $eventService
     * @return Responsable
     */
    public function handleDocumentEngineEvent(NewEvent                   $request,
                                              DocumentEngineEventService $eventService): Responsable
    {
        return $eventService
            ->setCauser($request->user())
            ->processEvent($request->getEventData());
    }
}