<?php

namespace App\Services;

use App\Contracts\Services\MaintenanceServiceInterface;
use App\Contracts\Repositories\System\BuildRepositoryInterface as Builds;
use App\Models\System\Build;
use Illuminate\Support\Facades\File;

class MaintenanceService implements MaintenanceServiceInterface
{
    public const STATUS_STOPPED = 0, STATUS_RUNNING = 1, STATUS_SCHEDULED = 2;

    protected Builds $builds;

    public function __construct(Builds $builds)
    {
        $this->builds = $builds;
    }

    public function status(): int
    {
        return $this->interpretStatusOf($this->builds->last());
    }

    public function running(): bool
    {
        return $this->status() === static::STATUS_RUNNING;
    }

    public function stopped(): bool
    {
        return $this->status() === static::STATUS_STOPPED;
    }

    public function scheduled(): bool
    {
        return $this->status() === static::STATUS_SCHEDULED;
    }

    public function interpretStatusOf(?Build $build): int
    {
        if (null === $build) {
            return static::STATUS_STOPPED;
        }

        if (!isset($build->start_time) || !isset($build->end_time)) {
            return static::STATUS_STOPPED;
        }

        if (now()->lt($build->start_time)) {
            return static::STATUS_SCHEDULED;
        }

        if (now()->gte($build->start_time) && now()->lte($build->end_time)) {
            return static::STATUS_RUNNING;
        }

        return static::STATUS_STOPPED;
    }

    public function putData(): void
    {
        $build = $this->builds->last();

        $content = [
            'start_time' => $build->start_time->toISOString(),
            'end_time'   => $build->end_time->toISOString(),
            'created_at' => now()->toISOString()
        ];

        File::ensureDirectoryExists(static::maintenanceDirectory());

        rescue(fn () => File::put(static::dataPath(), json_encode($content, JSON_PRETTY_PRINT)));
    }

    public function getData(): array
    {
        if (!File::exists(static::dataPath())) {
            return [];
        }

        return json_decode(File::get(static::dataPath()), true);
    }

    protected static function maintenanceDirectory(): string
    {
        return ui_path('maintenance');
    }

    protected static function dataPath(): string
    {
        return ui_path(config('maintenance_data_path', 'maintenance/maintenance_data.json'));
    }
}
