<?php

namespace App\Console\Commands;

use App\Models\{Template\TemplateField, Template\TemplateFieldType};
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Throwable;

class UpdateTemplateFields extends Command
{
    /**
     * The name and signature of the console command.s
     *
     * @var string
     */
    protected $signature = 'eq:update-template-fields';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update System Defined Template Fields';

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
     * @param ConnectionInterface $connection
     * @return mixed
     * @throws Throwable
     */
    public function handle(ConnectionInterface $connection)
    {
        $this->output->title('Updating Template Fields...');

        $seeds = json_decode(file_get_contents(database_path('seeders/models/template_fields.json')), true);

        $connection->beginTransaction();

        $this->output->progressStart(count($seeds));

        try {
            foreach ($seeds as $seed) {
                $typeKey = TemplateFieldType::where('name', $seed['type'])->value('id');

                $field = array_merge($seed, [
                    'template_field_type_id' => $typeKey,
                    'is_system' => true
                ]);

                $templateField = TemplateField::firstOrCreate(['name' => $field['name']], $field);

                if (false === $templateField->wasRecentlyCreated) {
                    $templateField->update($field);
                }

                $this->output->progressAdvance();

            }

            $connection->commit();
        } catch (Throwable $e) {
            $connection->rollBack();

            throw $e;
        }

        $this->output->progressFinish();

        $this->info("Template Fields have been updated!");
    }
}
