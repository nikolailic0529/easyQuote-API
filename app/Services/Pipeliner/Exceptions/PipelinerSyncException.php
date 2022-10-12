<?php

namespace App\Services\Pipeliner\Exceptions;

use App\Models\Contact;
use App\Models\SalesUnit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\HttpFoundation\Response;

class PipelinerSyncException extends PipelinerException
{
    #[Pure]
    public static function unsetSalesUnit(): static
    {
        return new static("Sales unit must be set.");
    }

    public static function nonAllowedSalesUnit(SalesUnit $unit): static
    {
        return new static ("The sales unit [$unit->unit_name] not allowed for synchronization.");
    }

    public static function noSalesUnitIsEnabled(): static
    {
        return new static("No sales unit is enabled.");
    }

    public static function undefinedContactAddressRelation(Contact $contact): static
    {
        return new static("Contact [{$contact->getIdForHumans()}] must be associated with the address.");
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->message], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}