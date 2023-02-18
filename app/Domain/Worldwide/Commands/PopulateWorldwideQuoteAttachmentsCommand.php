<?php

namespace App\Domain\Worldwide\Commands;

use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteAttachmentService;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionResolverInterface;

class PopulateWorldwideQuoteAttachmentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:populate-ww-quote-attachments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate worldwide quote attachments';

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
     *
     * @throws \Throwable
     */
    public function handle(
        WorldwideQuoteAttachmentService $service,
        ConnectionResolverInterface $connectionResolver
    ): int {
        $max = WorldwideQuote::query()
            ->whereNotNull('submitted_at')
            ->count();

        $bar = $this->output->createProgressBar($max);

        $connectionResolver->connection()->transaction(function () use ($service, $bar): void {
            WorldwideQuote::query()
                ->whereNotNull('submitted_at')
                ->lazyById(100)
                ->each(static function (WorldwideQuote $quote) use ($bar, $service): void {
                    rescue(fn () => $service->createAttachmentFromSubmittedQuote($quote), rescue: static function () use ($quote): void {
                        $this->warn("Could not create attachment from submitted quote [{$quote->getKey()}].");
                    });
                    rescue(fn () => $service->createAttachmentFromDistributorFiles($quote), rescue: static function () use ($quote): void {
                        $this->warn("Could not create attachment from distributor files. Quote {$quote->getKey()}].");
                    });

                    $bar->advance();
                });
        });

        $bar->finish();

        return self::SUCCESS;
    }
}
