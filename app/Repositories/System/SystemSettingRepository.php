<?php

namespace App\Repositories\System;

use App\Contracts\Repositories\System\SystemSettingRepositoryInterface;
use App\Models\System\SystemSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class SystemSettingRepository implements SystemSettingRepositoryInterface
{
    protected $systemSetting;

    public function __construct(SystemSetting $systemSetting)
    {
        $this->systemSetting = $systemSetting;
    }

    public function find(string $id): SystemSetting
    {
        return $this->systemSetting->whereId($id)->firstOrFail();
    }

    public function get(string $key)
    {
        $setting = $this->systemSetting->where('key', $key)->firstOrNew([]);

        return $setting->value;
    }

    public function update($attributes, string $id): bool
    {
        if ($attributes instanceof Request) {
            $attributes = $attributes->validated();
        }

        if (!is_array($attributes)) {
            return false;
        }

        return $this->find($id)->update($attributes);
    }

    public function all(): Collection
    {
        return $this->systemSetting->whereNotIn('key', ['parser.default_separator', 'parser.default_page'])->get();
    }
}
