<?php

namespace App\Domain\Mail\Controllers\V1;

use App\Domain\Mail\Models\MailLog;
use App\Domain\Mail\Queries\MailLogQueries;
use App\Domain\Mail\Resources\V1\MailLogListResource;
use App\Domain\Mail\Resources\V1\MailLogResource;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MailLogController extends Controller
{
    /**
     * Paginate mail log.
     *
     * @throws AuthorizationException
     */
    public function paginateMailLog(Request $request, MailLogQueries $queries): AnonymousResourceCollection
    {
        $this->authorize('viewAny', MailLog::class);

        $pagination = $queries->paginateMailLogQuery($request)->apiPaginate();

        return MailLogListResource::collection($pagination);
    }

    /**
     * Show mail log record.
     *
     * @throws AuthorizationException
     */
    public function showMailLogRecord(Request $request, MailLog $record): MailLogResource
    {
        $this->authorize('view', $record);

        return MailLogResource::make($record);
    }
}
