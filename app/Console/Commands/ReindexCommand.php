<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elasticsearch\Client as ElasticsearchClient;
use App\Models\{
    Address,
    User,
    Role,
    Company,
    Vendor,
    Contact,
    Quote\Quote,
    QuoteTemplate\QuoteTemplate,
    QuoteTemplate\TemplateField,
    Quote\Margin\CountryMargin,
    Quote\Discount\MultiYearDiscount,
    Quote\Discount\PrePayDiscount,
    Quote\Discount\PromotionalDiscount,
    Quote\Discount\SND,
    Collaboration\Invitation,
    System\Activity,
    Data\Country
};
use App\Models\Quote\Contract;
use Str;

class ReindexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:search-reindex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Indexes all entries to Elasticsearch';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ElasticsearchClient $elasticsearch)
    {
        parent::__construct();

        $this->elasticsearch = $elasticsearch;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /**
         * Perform deleting on all indices
         */
        $this->info("Deleting all indexes...");
        $this->elasticsearch->indices()->delete(['index' => '_all']);

        $this->handleModels(
            [
                User::class,
                Role::class,
                Quote::class,
                Contract::class,
                QuoteTemplate::class,
                TemplateField::class,
                CountryMargin::class,
                MultiYearDiscount::class,
                PrePayDiscount::class,
                PromotionalDiscount::class,
                SND::class,
                Company::class,
                Vendor::class,
                Invitation::class,
                Activity::class,
                Address::class,
                Contact::class,
                Country::class
            ]
        );
    }

    private function handleModels(array $models)
    {
        foreach ($models as &$model) {
            $plural = Str::plural(class_basename($model));

            $this->comment("Indexing all {$plural}...");

            $bar = $this->output->createProgressBar($model::count());

            $model = app($model);
            $model->unsetEventDispatcher();

            $cursor = $model::on('mysql_unbuffered')->cursor();

            $cursor->each(function ($entry) use ($bar) {
                $this->elasticsearch->index([
                    'index' => $entry->getSearchIndex(),
                    'id' => $entry->getKey(),
                    'body' => $entry->toSearchArray(),
                ]);

                $bar->advance();
            });

            $bar->finish();

            $this->info("\nDone!");
        }
    }
}
