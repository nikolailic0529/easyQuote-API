<?php

namespace App\Domain\Template\Commands;

use App\Domain\Rescue\Models\ContractTemplate;
use App\Domain\Rescue\Models\QuoteTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class UpdateTemplatesAssetsCommand extends Command
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
        if (!class_exists($model)) {
            throw new \RuntimeException('Invalid Model Class provided');
        }

        $pluralizedModel = Str::plural(class_basename($model));

        $this->output->title("\nUpdating the {$pluralizedModel} assets...");

        /** @var \Illuminate\Database\Eloquent\Model */
        $model = new $model();

        $this->output->progressStart($model->count());

        $model->on('mysql_unbuffered')->select(['id', 'company_id', 'vendor_id', 'form_data'])
            ->cursor()
            ->each(function ($template) {
                $this->updateTemplateAssets($template);

                $this->output->progressAdvance();
            });

        $this->output->progressFinish();

        $this->info("\n{$pluralizedModel} assets were updated!");
    }

    protected function updateTemplateAssets($template): void
    {
        $templateClassname = class_basename($template);
        $errorMessage = "{$templateClassname} {$template->name} was not updated because the data was corrupted!";

        if (is_null($template->form_data)) {
            return;
        }

        $template->load('vendor:id', 'company:id');

        $assets = collect($template->vendor->logoSelectionWithKeys)->merge($template->company->logoSelectionWithKeys);
        $controls = [];

        try {
            $controls = Arr::where(
                Arr::dot($template->form_data),
                fn ($value, $key) => is_string($value) && preg_match('/\.id$/', $key) && $assets->has($value)
            );
        } catch (\Throwable $e) {
            $this->error($errorMessage);

            return;
        }

        if (empty($controls)) {
            return;
        }

        $formData = $template->form_data;

        collect($controls)->each(function ($name, $key) use (&$formData, $assets) {
            data_set($formData, Str::replaceLast('.id', '.src', $key), $assets[$name]);
        });

        /* We are preventing update with null form_data as it means that something went wrong when parsing. */
        if (is_null(json_decode(json_encode($formData), true))) {
            $this->error($errorMessage);

            return;
        }

        $template->query()->whereKey($template->getKey())->toBase()->update(['form_data' => $formData]);
    }
}
