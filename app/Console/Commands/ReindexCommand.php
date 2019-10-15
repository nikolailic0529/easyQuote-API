<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elasticsearch\Client as ElasticsearchClient;
use App\Models \ {
    Company,
    Vendor,
    Quote\Quote,
    Quote\Margin\CountryMargin,
    QuoteTemplate\QuoteTemplate
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
        $this->handleModel(Quote::class);
        $this->handleModel(CountryMargin::class);
        $this->handleModel(Company::class);
        $this->handleModel(Vendor::class);
        $this->handleModel(QuoteTemplate::class);
    }

    private function handleModel(string $class)
    {
        $plural = Str::plural(class_basename($class));

        $this->info("Indexing all {$plural}...");

        foreach ($class::cursor() as $quote) {
            $this->elasticsearch->index([
                'index' => $quote->getSearchIndex(),
                'type' => $quote->getSearchType(),
                'id' => $quote->getKey(),
                'body' => $quote->toSearchArray(),
            ]);

            $this->output->write('.');
        }

        $this->info("\nDone!");
    }
}
