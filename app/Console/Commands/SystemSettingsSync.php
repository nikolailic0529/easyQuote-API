<?php

namespace App\Console\Commands;

use App\Models\System\SystemSetting;
use Illuminate\Console\Command;
use Illuminate\Support\{Str, Arr};
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemSettingsSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:settings-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize the System Settings';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Updating System Settings...');

        activity()->disableLogging();

        $settings = json_decode(file_get_contents(database_path('seeds/models/system_settings.json')), true);

        DB::beginTransaction();

        try {
            collect($settings)->each(function ($setting) {
                $key = $setting['key'];
                $value = $this->formatValue($key, $setting['value']);
                $possibleValues = $this->formatPossibleValues(optional($setting)['possible_values']);
    
                $attributes = [
                    'key'               => $key,
                    'value'             => $value,
                    'type'              => $setting['type'] ?? 'string',
                    'possible_values'   => $possibleValues,
                    'section'           => $setting['section'],
                    'is_read_only'      => $setting['is_read_only'] ?? false,
                    'label_format'      => $setting['label_format'] ?? null,
                    'order'             => $setting['order'] ?? 1,
                    'validation'        => $setting['validation'] ?? null
                ];
    
                $setting = SystemSetting::firstOrNew(['key' => $key]);
                
                if (!$setting->exists) {
                    $setting->forceFill($attributes)->save();
                }
    
                $this->output->write('.');
            });
        
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        activity()->enableLogging();

        $this->info("\nSystem Settings were synchronized!");
    }

    protected function formatValue(string $key, $value)
    {
        if ($key === 'failure_report_recipients') {
            return app('user.repository')->findByEmail($value)->pluck('id')->toArray();
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && Str::contains($value, 'CONST:')) {
            return constant(Str::after($value, 'CONST:'));
        }

        return $value;
    }

    protected function formatPossibleValues($values)
    {
        if (is_string($values) && Str::containsAll($values, ['RANGE:', ',', '{value}'])) {
            $range = Str::after($values, 'RANGE:');
            $parameters = explode(',', $range);

            if (count($parameters) < 3) {
                return $values;
            }

            $from = (int) $parameters[0];
            $to = (int) $parameters[1];

            if (count($parameters) > 3) {
                $each = (int) $parameters[2];
                $label = $parameters[3];
            } else {
                $each = 1;
                $label = $parameters[2];
            }

            $values = [];

            for ($i = $from; $i <= $to; $i++) {
                if ($i % $each !== 0) {
                    continue;
                }

                array_push($values, [
                    'label' => str_replace('{value}', $i, $label),
                    'value' => $i
                ]);
            }

            return $values;
        }

        return $values;
    }
}
