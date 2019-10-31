<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elasticsearch\Client as ElasticsearchClient;
use App\Models \ {
    User,
    Role,
    Company,
    Vendor,
    Quote\Quote,
    QuoteTemplate\QuoteTemplate,
    QuoteTemplate\TemplateField,
    Quote\Margin\CountryMargin,
    Quote\Discount\MultiYearDiscount,
    Quote\Discount\PrePayDiscount,
    Quote\Discount\PromotionalDiscount,
    Quote\Discount\SND
};
use Str;

class ReindexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:reindex';

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
        $this->handleModels(
            [
                User::class,
                Role::class,
                Quote::class,
                QuoteTemplate::class,
                TemplateField::class,
                CountryMargin::class,
                MultiYearDiscount::class,
                PrePayDiscount::class,
                PromotionalDiscount::class,
                SND::class,
                Company::class,
                Vendor::class
            ]
        );
    }

    private function handleModels(array $models)
    {
        foreach ($models as $model) {
            $plural = Str::plural(class_basename($model));

            $this->info("Indexing all {$plural}...");

            foreach ($model::cursor() as $entry) {
                $this->elasticsearch->index([
                    'index' => $entry->getSearchIndex(),
                    'type' => $entry->getSearchType(),
                    'id' => $entry->getKey(),
                    'body' => $entry->toSearchArray(),
                ]);

                $this->output->write('.');
            }

            $this->info("\nDone!");
        }
    }
}
