<?php

namespace App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit;

use App\Domain\Worldwide\Enum\SalesOrderStatus;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Response;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class SubmitSalesOrderResult extends DataTransferObject implements Responsable
{
    /**
     * @Constraints\Choise({0,1,2,3})
     */
    public int $status;

    public ?string $status_reason;

    /**
     * {@inheritDoc}
     */
    public function toResponse($request)
    {
        if ($this->status === SalesOrderStatus::SENT) {
            return response()->json([
                'result' => 'OK',
            ], Response::HTTP_ACCEPTED);
        }

        return response()->json([
            'result' => 'failure',
            'failure_reason' => $this->status_reason,
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
