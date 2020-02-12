<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Contracts\Repositories\UserRepositoryInterface as User;
use Str, Arr;

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

    /** @var \App\Contracts\Repositories\UserRepositoryInterface */
    protected $user;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        parent::__construct();

        $this->user = $user;
    }

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

        \DB::transaction(function () use ($settings) {
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
                    'label_format'      => $setting['label_format'] ?? null
                ];

                $setting = setting()->firstOrCreate(
                    compact('key'),
                    $attributes
                );

                if (!$setting->wasRecentlyCreated) {
                    $setting->update(Arr::except($attributes, 'value'));
                }

                $this->output->write('.');
            });
        });

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
            $label = $parameters[2];

            $values = [];

            for ($i = $from; $i <= $to; $i++) {
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
