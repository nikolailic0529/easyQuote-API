<?php

namespace App\Mixins;

class ActivityLoggerMixin
{
    public function logWhen()
    {
        return function (string $description, $when) {
            return $when ? $this->log($description) : $this->activity;
        };
    }

    public function queueWhen()
    {
        return function (string $description, $when) {
            return $when ? $this->queue($description) : $this->activity;
        };
    }

    public function withAttribute()
    {
        return function (string $attribute, $new, $old) {
            return $this->withProperties(['attributes' => [$attribute => $new], 'old' => [$attribute => $old]]);
        };
    }
}
