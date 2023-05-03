<?php

namespace App\Domain\Pipeliner\Integration\Models;

class FilterInputCollection extends BaseFilterInput
{
    protected function __construct(protected array $filters)
    {
    }

    public static function from(BaseFilterInput ...$filters): static
    {
        return new static(array_values($filters));
    }

    public function jsonSerialize(): array
    {
        return array_map(static function (BaseFilterInput $input): mixed {
            return $input->jsonSerialize();
        }, $this->filters);
    }
}
