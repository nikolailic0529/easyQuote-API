<?php

namespace App\Repositories\System;

use App\Contracts\Repositories\System\SystemSettingRepositoryInterface;
use App\Models\System\SystemSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Str, Arr;

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
        return cache()->sear("setting-value:{$key}", function () use ($key) {
            $setting = $this->systemSetting->where('key', $key)->firstOrNew([]);
            $value = $setting->value;

            if ($this->hasGetMutator($key)) {
                return $this->mutateSetting($key, $value);
            }

            return $value;
        });
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

    public function getSupportedFileTypesSetting($value)
    {
        if (!in_array('CSV', $value)) {
            return $value;
        }

        array_push($value, 'TXT');
        return $value;
    }

    public function getSupportedFileTypesUiSetting()
    {
        return collect($this->get('supported_file_types'))
            ->transform(function ($type) {
                $type = strtolower($type);
                return __("setting.supported_file_types.{$type}");
            })->collapse();
    }

    public function getSupportedFileTypesRequestSetting()
    {
        return implode(',', Arr::lower($this->get('supported_file_types')));
    }

    public function getFileUploadSizeKbSetting()
    {
        return $this->get('file_upload_size') * 1000;
    }

    /**
     * Determine if a get mutator exists for a setting value.
     *
     * @param  string  $key
     * @return bool
     */
    protected function hasGetMutator($key)
    {
        return method_exists($this, 'get'.Str::studly($key).'Setting');
    }

    /**
     * Get the value of a setting value using its mutator.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateSetting($key, $value)
    {
        return $this->{'get'.Str::studly($key).'Setting'}($value);
    }
}
