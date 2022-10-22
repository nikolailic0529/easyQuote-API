<?php

namespace App\Services\Pipeliner\Exceptions;

use App\Contracts\ProvidesIdForHumans;
use App\Models\Contact;
use App\Models\SalesUnit;
use App\Services\Pipeliner\Contracts\ContainsRelatedEntities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\HttpFoundation\Response;

class PipelinerSyncException extends PipelinerException implements ContainsRelatedEntities
{
    protected array $related = [];

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

    public static function modelReferencesToDifferentEntity(Model $model): static
    {
        $modelName = class_basename($model);
        $idForHumans = $model instanceof ProvidesIdForHumans ? $model->getIdForHumans() : $model->getKey();

        return new static("$modelName [$idForHumans] already references to a different entity in Pipeliner.");
    }

    public static function modelProtectedFromSync(Model $model): static
    {
        $modelName = class_basename($model);
        $idForHumans = $model instanceof ProvidesIdForHumans ? $model->getIdForHumans() : $model->getKey();

        return new static("$modelName [$idForHumans] is protected from sync.");
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->message], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function relatedTo(object ...$entities): static
    {
        return tap($this, fn () => $this->related = $entities);
    }

    public function getRelated(): array
    {
        return $this->related;
    }
}