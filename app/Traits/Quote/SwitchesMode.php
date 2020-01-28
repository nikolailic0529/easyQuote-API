<?php

namespace App\Traits\Quote;

trait SwitchesMode
{
    /** @var string */
    protected $mode = QT_TYPE_QUOTE;

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

    public function switchModeTo(string $type): self
    {
        throw_unless(isset(array_flip(QT_TYPES)[$type]), new \InvalidArgumentException(INV_QT_TYPE));

        return $this->tap(function ($model) use ($type) {
            $this->mode = $type;
        });
    }

    public function getModeAttribute()
    {
        return $this->mode;
    }

    public function isMode(string $type): bool
    {
        return $this->mode === $type;
    }

    public function getModeTemplateAttribute()
    {
        return $this->{$this->mode.'Template'};
    }
}
