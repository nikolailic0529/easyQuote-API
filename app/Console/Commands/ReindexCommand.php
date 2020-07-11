<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elasticsearch\Client as ElasticsearchClient;
use App\Models\{
    Task,
    Address,
    Asset,
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
    QuoteFile\ImportableColumn,
};
use App\Models\QuoteTemplate\ContractTemplate;
use App\Models\QuoteTemplate\HpeContractTemplate;
use Illuminate\Database\Eloquent\Builder;
use Str;
use Throwable;

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

    protected ElasticsearchClient $elasticsearch;

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

        try {
            $this->elasticsearch->indices()->delete(['index' => '_all']);
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            $this->error("Reindexing will be skipped.");

            return false;
        }

        $this->handleModels(
            [
                User::class,
                Role::class,
                Quote::class,
                Contract::class,
                QuoteTemplate::class,
                ContractTemplate::class,
                HpeContractTemplate::class,
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
                Asset::class,
                ImportableColumn::regular(),
            ]
        );

        $this->info('Reindex has been successfully finished!');

        return true;
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
            $model->setConnection(MYSQL_UNBUFFERED);

            $plural = Str::plural(class_basename($model));

            $this->comment("Indexing all {$plural}...");

            $bar = $this->output->createProgressBar($query->count());

            $cursor = $query->cursor();

            /** Limited indexing for testing environment. */
            if (app()->runningUnitTests()) {
                $cursor = $cursor->take(10);
            }

            rescue(
                fn () =>
                $cursor->each(function ($entry) use ($bar) {
                    $this->elasticsearch->index([
                        'id'    => $entry->getKey(),
                        'index' => $entry->getSearchIndex(),
                        'body'  => $entry->toSearchArray()
                    ]);

                    $bar->advance();
                }),
                fn (Throwable $exception) => $this->error($exception->getMessage())
            );

            $bar->finish();

            $this->info("\nDone!");
        }
    }
}
