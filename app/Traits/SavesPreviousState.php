<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Arr;

trait SavesPreviousState
{
    protected static function bootSavesPreviousState()
    {
        static::updating(function (Model $model) {
            $model->forceFill(['previous_state' => json_encode($model->getCurrentState(), true)]);
        });
    }

    public function getPreviousStateAttribute($value): array
    {
        return json_decode($value, true) ?? [];
    }

    protected function getCurrentState(): array
    {
        $state = $this->getOriginal();

        if (isset(static::$saveStateAttributes) && is_array(static::$saveStateAttributes)) {
            $state = Arr::only($state, static::$saveStateAttributes);
        }

        return Arr::except($state, 'previous_state');
    }
}
