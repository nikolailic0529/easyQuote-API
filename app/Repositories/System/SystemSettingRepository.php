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

    public function findMany(array $ids): Collection
    {
        return $this->systemSetting->whereIn('id', $ids)->get();
    }

    public function get(string $key)
    {
        $setting = $this->systemSetting->where('key', $key)->firstOrNew([]);
        $value = $setting->value;

        if ($key === 'supported_file_types' && is_array($value) && in_array('CSV', $value)) {
            array_push($value, 'TXT');
        }

        return $value;
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

    public function updateMany($attributes): bool
    {
        if ($attributes instanceof Request) {
            $attributes = $attributes->validated();
        }

        if (!is_array($attributes) || !is_array(head($attributes))) {
            return false;
        }

        $systemSettings = $this->findMany(data_get($attributes, '*.id'));

        $attributes = collect($attributes);

        $updated = $systemSettings->reduce(function ($carry, $systemSetting) use ($attributes) {
            $systemSetting->value = data_get($attributes->firstWhere('id', '===', $systemSetting->id), 'value');
            $carry->push($systemSetting->save());
            return $carry;
        }, collect());

        return $systemSettings->count() === $updated->filter()->count();
    }

    public function all(): Collection
    {
        return $this->systemSetting->whereNotIn('key', ['parser.default_separator', 'parser.default_page'])
            ->orderBy('is_read_only')
            ->get();
    }
}
