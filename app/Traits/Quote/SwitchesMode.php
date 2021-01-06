<?php

namespace App\Traits\Quote;

use App\Models\Template\BaseQuoteTemplate;

trait SwitchesMode
{
    protected int $mode = QT_TYPE_QUOTE;

    public function switchToQuote(): self
    {
        return $this->tap(function ($model) {
            $this->mode = QT_TYPE_QUOTE;
        });
    }

    public function switchToContract(): self
    {
        return $this->tap(function ($model) {
            $this->mode = QT_TYPE_CONTRACT;
        });
    }

    public function switchModeTo($type): self
    {
        throw_unless(isset(QT_TYPES[$type]), new \InvalidArgumentException(INV_QT_TYPE));

        return tap($this, function () use ($type) {
            $this->mode = $type;
        });
    }

    public function getModeAttribute(): int
    {
        return $this->mode;
    }

    public function isMode($type): bool
    {
        return $this->mode === $type;
    }

    public function getModeTemplateAttribute()
    {
        throw_unless(isset(QT_TYPES[$this->mode]), new \InvalidArgumentException(INV_QT_TYPE));

        $mode = QT_TYPES[$this->mode];

        return $this->{$mode.'Template'};
    }
}
