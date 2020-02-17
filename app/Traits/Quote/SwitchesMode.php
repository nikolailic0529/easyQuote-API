<?php

namespace App\Traits\Quote;

use App\Models\QuoteTemplate\BaseQuoteTemplate;

trait SwitchesMode
{
    /** @var string */
    protected string $mode = QT_TYPE_QUOTE;

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

    public function getModeAttribute(): string
    {
        return $this->mode;
    }

    public function isMode(string $type): bool
    {
        return $this->mode === $type;
    }

    public function getModeTemplateAttribute(): BaseQuoteTemplate
    {
        return $this->{$this->mode.'Template'};
    }
}
