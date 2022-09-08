<?php

namespace App\Services\Settings\ValueProviders;

class RangeValueProvider implements ValueProvider
{
    public function __construct(
        protected readonly int $start,
        protected readonly int $end,
        protected readonly int $step = 1,
        protected readonly string $label = ":value",
    ) {
    }

    public function __invoke(): array
    {
        return collect(range($this->start, $this->end, $this->step))
            ->map(function (int $value) {
                return [
                    'label' => __(trans_choice($this->label, $value), ['value' => $value]),
                    'value' => $value,
                ];
            })
            ->all();
    }
}