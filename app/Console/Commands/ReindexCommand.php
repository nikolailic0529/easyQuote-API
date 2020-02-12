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
    Quote\Contract,
    QuoteTemplate\QuoteTemplate,
    QuoteTemplate\TemplateField,
    Quote\Margin\CountryMargin,
    Quote\Discount\MultiYearDiscount,
    Quote\Discount\PrePayDiscount,
    Quote\Discount\PromotionalDiscount,
    Quote\Discount\SND,
    Collaboration\Invitation,
    System\Activity,
    Data\Country,
    QuoteFile\ImportableColumn
};
use Illuminate\Database\Eloquent\Builder;
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
                Country::class,
                ImportableColumn::regular()
            ]
        );
    }

    private function handleModels(array $models)
    {
        foreach ($models as &$model) {
            if ($model instanceof Builder) {
                $query = $model;
                $model = $model->getModel();
            } else {
                $model = app($model);
                $query = $model->query();
            }

            $model->unsetEventDispatcher();
            $model->setConnection('mysql_unbuffered');

            $plural = Str::plural(class_basename($model));

            $this->comment("Indexing all {$plural}...");

            $bar = $this->output->createProgressBar($query->count());

            $query->cursor()->each(function ($entry) use ($bar) {
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
