<?php

namespace App\Repositories\System;

use App\Contracts\Repositories\System\SystemSettingRepositoryInterface;
use App\Models\System\SystemSetting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Str, Arr, DB;

class SystemSettingRepository implements SystemSettingRepositoryInterface
{
    protected SystemSetting $systemSetting;

    public function __construct(SystemSetting $systemSetting)
    {
        $this->systemSetting = $systemSetting;
    }

    public function find(string $id): SystemSetting
    {
        return $this->systemSetting->whereId($id)->firstOrFail();
    }

    public function findByKey(string $key): SystemSetting
    {
        return $this->systemSetting->where('key', $key)->firstOrFail();
    }

    public function findMany(array $ids): Collection
    {
        return $this->systemSetting->whereIn('id', $ids)->get();
    }

    public function get(string $key, bool $mutate = true)
    {
        if ($mutate && $this->hasGetMutator($key)) {
            return $this->mutateSetting($key);
        }

        return cache()->sear("setting-value:{$key}", function () use ($key) {
            $setting = $this->systemSetting->where('key', $key)->firstOrNew([]);
            $value = $setting->value;

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

    public function firstOrCreate(array $attributes, array $values = [])
    {
        return $this->systemSetting->firstOrCreate($attributes, $values);
    }

    public function updateOrCreate(array $attributes, array $values = [])
    {
        return $this->systemSetting->updateOrCreate($attributes, $values);
    }

    public function updateByKeys(array $map): bool
    {
        DB::transaction(
            fn () =>
            $this->systemSetting->whereIn('key', array_keys($map))->get()
                ->each(fn (SystemSetting $setting) => $setting->update(['value' => $map[$setting->key]]))
        );

        return true;
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

        $attributes = collect($attributes)->keyBy('id');

        $updated = DB::transaction(function () use ($systemSettings, $attributes) {
            return $systemSettings->reduce(function ($carry, $systemSetting) use ($attributes) {
                $systemSetting->value = data_get($attributes->get($systemSetting->id), 'value');
                $carry->push($systemSetting->save());
                return $carry;
            }, collect());
        }, 3);

        return $systemSettings->count() === $updated->filter()->count();
    }

    public function all(): Collection
    {
        return $this->systemSetting
            ->whereNotIn('key', ['parser.default_separator', 'parser.default_page'])
            ->orderBy('order')
            ->get();
    }

    protected function getNotificationTimeSetting()
    {
        $value = $this->get('notification_time', false);

        return \Carbon\CarbonInterval::create(0, 0, $value);
    }

    protected function getFailureReportRecipientsSetting()
    {
        $value = $this->get('failure_report_recipients', false) ?? [];

        return app('user.repository')->findMany($value);
    }

    protected function getSupportedFileTypesSetting()
    {
        $value = (array) $this->get('supported_file_types', false);

        if (!in_array('CSV', $value)) {
            return $value;
        }

        array_push($value, 'TXT');
        return $value;
    }

    protected function getSupportedFileTypesUiSetting()
    {
        return collect($this->get('supported_file_types', false))
            ->transform(function ($type) {
                $type = strtolower($type);
                return __("setting.supported_file_types.{$type}");
            })->collapse();
    }

    protected function getSupportedFileTypesRequestSetting()
    {
        return implode(',', Arr::lower($this->get('supported_file_types', true)));
    }

    protected function getFileUploadSizeKbSetting()
    {
        return $this->get('file_upload_size', false) * 1000;
    }

    protected function getExchangeRateUpdateSchedule()
    {
        $value = $this->get('exchange_rate_update_schedule', false);

        return is_null($value) ? ER_UPD_DEFAULT_SCHEDULE : $value;
    }

    /**
     * Determine if a get mutator exists for a setting value.
     *
     * @param  string  $key
     * @return bool
     */
    protected function hasGetMutator($key)
    {
        $key = Str::studly(str_replace('.', '_', $key));
        return method_exists($this, 'get' . $key . 'Setting');
    }

    /**
     * Get the value of a setting value using its mutator.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function mutateSetting($key)
    {
        return $this->{'get' . Str::studly($key) . 'Setting'}();
    }
}
