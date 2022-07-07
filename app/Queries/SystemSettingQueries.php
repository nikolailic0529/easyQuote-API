<?php

namespace App\Queries;

use App\Models\System\SystemSetting;
use Illuminate\Database\Eloquent\Builder;

class SystemSettingQueries
{
    public function listSystemSettingsQuery(): Builder
    {
        return SystemSetting::query()
            ->whereNotIn('key', ['parser.default_separator', 'parser.default_page'])
            ->orderBy('order');
    }

    public function listPublicSystemSettingsQuery(): Builder
    {
        return $this->listSystemSettingsQuery()
            ->whereIn('key', config('settings.public'));
    }
}