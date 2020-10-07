<?php

namespace App\Console\Commands;

use App\Models\{
    QuoteTemplate\QuoteTemplate,
    QuoteTemplate\TemplateField,
    QuoteTemplate\TemplateFieldType
};
use Illuminate\Console\Command;

class TemplateFieldsUpdate extends Command
{
    /**
     * The name and signature of the console command.s
     *
     * @var string
     */
    protected $signature = 'eq:templatefields-update';

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
     * @return mixed
     */
    public function handle()
    {
        $this->info('Updating System Defined Template Fields...');

        activity()->disableLogging();

        \DB::transaction(function () {
            $templateFieldsData = json_decode(file_get_contents(database_path('seeds/models/template_fields.json')), true);

            $templateFields = collect($templateFieldsData)->map(function ($field) {
                $template_field_type_id = TemplateFieldType::whereName($field['type'])->value('id');
                $field = array_merge($field, compact('template_field_type_id'));

                $templateField = TemplateField::firstOrCreate(['name' => $field['name'], 'is_system' => true], $field);

                if (!$templateField->wasRecentlyCreated) {
                    $templateField->update($field);
                }

                $this->output->write('.');

                return $templateField->id;
            });

            QuoteTemplate::all('id')->each(fn (QuoteTemplate $template) => $template->templateFields()->syncWithoutDetaching($templateFields));
        });

        activity()->enableLogging();

        $this->info("\nSystem Defined Template Fields were updated!");
    }
}
