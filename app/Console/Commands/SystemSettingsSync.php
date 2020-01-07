<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
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
                $possibleValues = optional($setting)['possible_values'];

                setting()->updateOrCreate(
                    ['key' => $setting['key']],
                    [
                        'key' => $setting['key'],
                        'value' => $setting['value'],
                        'type' => $setting['type'] ?? 'string',
                        'possible_values' => $possibleValues,
                        'is_read_only' => $setting['is_read_only'] ?? false,
                        'label_format' => $setting['label_format'] ?? null
                    ]
                );

                $this->output->write('.');
            });
        });

        activity()->enableLogging();

        $this->info("\nSystem Settings were synchronized!");
    }
}
