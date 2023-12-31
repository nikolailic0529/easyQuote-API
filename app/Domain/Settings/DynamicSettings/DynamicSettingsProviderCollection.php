<?php

namespace App\Domain\Settings\DynamicSettings;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;

class DynamicSettingsProviderCollection extends LazyCollection
{
    public function toCollection(): Collection
    {
        return $this->map->__invoke()->pipeInto(Collection::class);
    }
}
