<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Ramsey\Uuid\UuidInterface;

/**
 * @mixin Model
 */
trait Uuid
{
    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    protected static function bootUuid(): void
    {
        static::creating(function (Model $model): void {
            // Only generate UUID if it wasn't set by already.
            if (!isset($model->attributes[$model->getKeyName()])) {
                $model->incrementing = false;
                $model->attributes[$model->getKeyName()] = static::generateUuid()->toString();
            }
        });
    }

    public static function generateUuid(): UuidInterface
    {
        return Str::orderedUuid();
    }

    public function setId(UuidInterface $value = null): static
    {
        $this->{$this->getKeyName()} = ($value ?? static::generateUuid())->toString();

        return $this;
    }
}
