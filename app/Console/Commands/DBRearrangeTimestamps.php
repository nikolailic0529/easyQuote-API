<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DBRearrangeTimestamps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:db-rearrange-timestamps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rearrange timestamps in all tables';

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
        $this->info("Rearranging timestamps in the existing tables...");

        $db = config('database.connections.mysql.database');
        $tables = collect(data_get(Schema::getAllTables(), "*.Tables_in_{$db}"));

        $knownTimestamps = ['created_at', 'updated_at', 'deleted_at', 'activated_at', 'drafted_at', 'submitted_at', 'expires_at', 'handled_at', 'automapped_at'];

        $tables->each(function ($table) use ($knownTimestamps) {
            $columns = collect(Schema::getColumnListing($table));
            $existingTimestamps = $columns->intersect($knownTimestamps)
                ->sortBy(function ($column) use ($knownTimestamps) {
                    return array_search($column, $knownTimestamps);
                });

            if (blank($existingTimestamps)) {
                return true;
            }

            $lastNonTimestampColumn = $columns->diff($knownTimestamps)->last();
            $firstTimestamp = $existingTimestamps->shift();

            DB::statement("alter table `{$table}` modify column `{$firstTimestamp}` timestamp null after `{$lastNonTimestampColumn}`");

            $existingTimestamps->reduce(function ($lastTimestamp, $timestamp) use ($table) {
                DB::statement("alter table `{$table}` modify column `{$timestamp}` timestamp null after `{$lastTimestamp}`");
                return $timestamp;
            }, $firstTimestamp);

            $this->comment("{$table} table is done!");
        });

        $this->info("\nTimestamps in the existing tables were rearranged...");
    }
}
