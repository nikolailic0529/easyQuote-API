<?php

namespace Tests\Unit\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

trait AssertsModelAttributes
{
    protected function assertModelAttributes(Model $model, $expected): void
    {
        Collection::wrap($expected)->each(function ($value, $key) use ($model) {
            if (($modelValue = $model->getOriginal($key)) instanceof Carbon) {
                $modelValue = $modelValue->format('Y-m-d');
            }

            $this->assertEquals($value, $modelValue);
        });
    }
}