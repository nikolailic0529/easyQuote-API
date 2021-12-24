<?php

namespace App\DTO\SalesOrder\Cancel;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Spatie\DataTransferObject\DataTransferObject;

final class CancelSalesOrderResult extends DataTransferObject implements Responsable
{
    public bool $response_ok;

    /**
     * @var string|null
     */
    public ?string $status_reason;

    /**
     * @inheritDoc
     */
    public function toResponse($request)
    {
        if (true === $this->response_ok) {
            return response()->json([
                'result' => 'OK'
            ], Response::HTTP_ACCEPTED);
        }

        return response()->json([
            'result' => 'failure',
            'failure_reason' => $this->status_reason
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
