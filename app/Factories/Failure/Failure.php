<?php

namespace App\Factories\Failure;

use App\Contracts\Factories\FailureInterface;
use Illuminate\Support\Collection;
use File;

class Failure implements FailureInterface
{
    protected static $reasonsPath;

    protected static $resolvingPath;

    protected static $reasons;

    protected static $resolving;

    public function __construct()
    {
        static::$reasonsPath = __DIR__ . DIRECTORY_SEPARATOR . 'reasons.json';
        static::$resolvingPath = __DIR__ . DIRECTORY_SEPARATOR . 'resolving.json';
    }

    public function __get($name)
    {
        $method = 'get' . ucfirst($name);

        if (!method_exists($this, $method)) {
            return;
        }

        return $this->{$method}();
    }

    public function helpFor(\Throwable $exception): FailureHelp
    {
        return FailureHelp::create($exception, $this->reasonsFor($exception), $this->resolvingFor($exception));
    }

    public function reasonsFor(\Throwable $exception): Collection
    {
        $class = get_class($exception);
        $found = $this->reasons->firstWhere('exception', '===', $class);

        $reasons = collect(data_get($found, 'reasons', []));

        return $reasons;
    }

    public function resolvingFor(\Throwable $exception): Collection
    {
        $class = get_class($exception);
        $found = $this->resolving->firstWhere('exception', '===', $class);

        $resolving = collect(data_get($found, 'resolving', []));

        return $resolving;
    }

    public function getReasons(): Collection
    {
        if (isset(static::$reasons)) {
            return static::$reasons;
        }

        return static::$reasons = static::reasons();
    }

    public function getResolving(): Collection
    {
        if (isset(static::$resolving)) {
            return static::$resolving;
        }

        return static::$resolving = static::resolving();
    }

    protected static function reasons(): Collection
    {
        return collect(json_decode(File::get(static::$reasonsPath), true));
    }

    protected static function resolving(): Collection
    {
        return collect(json_decode(File::get(static::$resolvingPath), true));
    }
}
