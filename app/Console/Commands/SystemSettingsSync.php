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

        \DB::transaction(function () {
            $settings = json_decode(file_get_contents(database_path('seeds/models/system_settings.json')), true);

            collect($settings)->each(function ($setting) {
                DB::table('system_settings')->updateOrInsert(
                    ['key' => $setting['key']],
                    [
                        'key' => $setting['key'],
                        'value' => is_array($setting['value']) ? json_encode($setting['value'], true) : $setting['value'],
                        'type' => $setting['type'] ?? 'string',
                        'possible_values' => isset($setting['possible_values']) ? json_encode($setting['possible_values'], true) : null,
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
