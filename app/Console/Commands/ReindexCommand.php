<?php

namespace App\Console\Commands;

use App\Contracts\ReindexQuery;
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
use Illuminate\Support\Str;
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
    public function handle(ElasticsearchClient $elasticsearch)
    {
        /**
         * Perform deleting on all indices
         */
        $this->info("Deleting all indexes...");

        try {
            $elasticsearch->indices()->delete(['index' => '_all']);
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
                Contract::reindexQuery(),
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
        /** @var ElasticsearchClient */
        $elasticsearch = app(ElasticsearchClient::class);

        foreach ($models as &$model) {
            [$model, $query] = static::modelQuery($model);

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
                $cursor->each(function ($entry) use ($bar, $elasticsearch) {
                    $elasticsearch->index([
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

    private static function modelQuery($model): array
    {
        if ($model instanceof Builder) {
            return [$model->getModel(), $model];
        }

        if (is_a($model, ReindexQuery::class, true)) {
            return [new $model, $model::reindexQuery()];
        }

        return [$model = (new $model), $model->query()];
    }
}
