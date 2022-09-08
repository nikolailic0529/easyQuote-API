<?php

namespace App\Services\DocumentProcessor\EasyQuote\Parsers\Models;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

final class PaymentScheduleData implements Arrayable
{
    public function __construct(
        public readonly string $from,
        public readonly string $to,
        public readonly string $price,
    ) {
    }

    public function toArray(): array
    {
        return $this->getReflectedProperties()
            ->lazy()
            ->mapWithKeys(function (\ReflectionProperty $property): \Generator {
                yield $property->getName() => $property->getValue($this);
            })
            ->toArray();
    }

    private function getReflectedProperties(): Collection
    {
        $class = new \ReflectionClass(PaymentScheduleData::class);

        return collect($class->getProperties(\ReflectionProperty::IS_PUBLIC))
            ->filter(static fn(\ReflectionProperty $property): bool => !$property->isStatic());
    }
}