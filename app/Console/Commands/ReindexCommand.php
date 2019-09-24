<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Elasticsearch\Client as ElasticsearchClient;
use App\Models\Quote\Quote;

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
    protected $description = 'Indexes all quotes to Elasticsearch';

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
        $this->info('Indexing all quotes...');

        foreach (Quote::cursor() as $quote)
        {
            $this->elasticsearch->index([
                'index' => $quote->getSearchIndex(),
                'type' => $quote->getSearchType(),
                'id' => $quote->getKey(),
                'body' => $quote->toSearchArray(),
            ]);

            $this->output->write('.');
        }

        $this->info('\nDone!');
    }
}
