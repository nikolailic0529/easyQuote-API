<?php

namespace App\Console\Commands;

use App\Models\QuoteTemplate\BaseQuoteTemplate;
use App\Models\QuoteTemplate\ContractTemplate;
use App\Models\QuoteTemplate\QuoteTemplate;
use Illuminate\Console\Command;
use Arr, Str;

class UpdateTemplatesAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:update-templates-assets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update templates assets such as vendor & company logos';

    /**
     * The Template models which will be handled.
     *
     * @var array
     */
    protected array $models = [QuoteTemplate::class, ContractTemplate::class];

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
        activity()->disableLogging();

        collect($this->models)->each(fn (string $model) => $this->updateModelTemplates($model));

        activity()->enableLogging();
    }

    protected function updateModelTemplates(string $model): void
    {
        $pluralizedModel = Str::plural(class_basename($model));

        $this->info("\nUpdating the {$pluralizedModel} assets...");

        app($model)->on('mysql_unbuffered')->cursor()
            ->each(fn (BaseQuoteTemplate $template) => $this->updateTemplateAssets($template));

        $this->info("\n{$pluralizedModel} assets were updated!");
    }

    protected function updateTemplateAssets(BaseQuoteTemplate $template): void
    {
        if (is_null($template->form_data)) {
            return;
        }

        $assets = collect($template->vendor->logoSelectionWithKeys)->merge($template->company->logoSelectionWithKeys);

        $controls = Arr::where(
            Arr::dot($template->form_data),
            fn ($value, $key) => is_string($value) && preg_match('/\.id$/', $key) && $assets->has($value)
        );

        if (empty($controls)) {
            return;
        }

        $form_data = $template->form_data;

        collect($controls)->each(function ($name, $key) use (&$form_data, $assets) {
            data_set($form_data, Str::replaceLast('.id', '.src', $key), $assets[$name]);
        });

        /** We are preventing update with null form_data as it means that something went wrong when parsing. */
        if (is_null(json_decode(json_encode($form_data), true))) {
            return;
        }

        $this->output->write('.');

        $template->update(compact('form_data'));
    }
}
