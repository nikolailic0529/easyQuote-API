<?php

namespace App\Domain\Formatting\Services;

use App\Domain\Formatting\Formatters\Formatter;
use App\Domain\Formatting\Services\Exceptions\FormatterResolvingException;
use Illuminate\Support\Str;

class FormatterDelegatorService
{
    /** @var array|Formatter[] */
    protected array $formatters;

    public function __construct(Formatter ...$formatters)
    {
        $this->registerFormatters(...$formatters);
    }

    public function getFormatters(): array
    {
        return $this->formatters;
    }

    private function registerFormatters(Formatter ...$formatters): void
    {
        foreach ($formatters as $name => $formatter) {
            $this->formatters[$formatter::class] = $formatter;

            if (is_string($name)) {
                $this->formatters[$name] = $formatter;
            }
        }
    }

    public function format(string $formatter,
                           mixed $value,
                           mixed ...$parameters): string
    {
        return $this->resolveFormatter($formatter)($value, ...$parameters);
    }

    protected function resolveFormatter(string $formatter): Formatter
    {
        if (array_key_exists($formatter, $this->formatters)) {
            return $this->formatters[$formatter];
        }

        $name = Str::kebab($formatter);

        if (array_key_exists($name, $this->formatters)) {
            return $this->formatters[$name];
        }

        throw FormatterResolvingException::unsupportedFormatter($formatter);
    }
}
