<?php

namespace App\Domain\Maintenance\Services;

use App\Domain\Build\Models\Build;
use App\Domain\Maintenance\Contracts\ManagesMaintenanceStatus;
use App\Domain\Maintenance\Enum\MaintenanceStatusEnum;
use Illuminate\Support\Facades\File;

class MaintenanceStatusService implements ManagesMaintenanceStatus
{
    public function status(): MaintenanceStatusEnum
    {
        /** @var Build $build */
        $build = Build::query()
            ->latest()
            ->firstOrNew();

        return $this->interpretStatusOf($build);
    }

    public function running(): bool
    {
        return $this->status() === MaintenanceStatusEnum::Running;
    }

    public function stopped(): bool
    {
        return $this->status() === MaintenanceStatusEnum::Stopped;
    }

    public function scheduled(): bool
    {
        return $this->status() === MaintenanceStatusEnum::Scheduled;
    }

    public function interpretStatusOf(?Build $build): MaintenanceStatusEnum
    {
        if (null === $build) {
            return MaintenanceStatusEnum::Stopped;
        }

        if (!isset($build->start_time) || !isset($build->end_time)) {
            return MaintenanceStatusEnum::Stopped;
        }

        if (now()->lt($build->start_time)) {
            return MaintenanceStatusEnum::Scheduled;
        }

        if (now()->gte($build->start_time) && now()->lte($build->end_time)) {
            return MaintenanceStatusEnum::Running;
        }

        return MaintenanceStatusEnum::Stopped;
    }

    public function writeMaintenanceData(): void
    {
        /** @var Build $build */
        $build = Build::query()
            ->latest()
            ->firstOrNew();

        $content = [
            'start_time' => $build->start_time?->toISOString(),
            'end_time' => $build->end_time?->toISOString(),
            'created_at' => now()->toISOString(),
        ];

        $filepath = $this->getMaintenanceFilepath();
        $directory = dirname($filepath);

        try {
            if (!is_dir($directory)) {
                mkdir(directory: $directory, permissions: 0755, recursive: true);
            }

            file_put_contents($filepath, json_encode($content, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function getMaintenanceData(): array
    {
        if (!File::exists($this->getMaintenanceFilepath())) {
            return [];
        }

        return json_decode(File::get($this->getMaintenanceFilepath()), true);
    }

    private function getMaintenanceFilepath(): string
    {
        return ui_path(config('ui.maintenance_data_path'));
    }
}
