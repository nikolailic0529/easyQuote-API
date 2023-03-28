<?php

namespace App\Domain\Attachment\Commands;

use App\Domain\Attachment\Services\AttachmentHashingService;
use Illuminate\Console\Command;

class HashAttachmentsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'eq:hash-attachments';

    /**
     * @var string
     */
    protected $description = 'Hash attachments';

    public function handle(AttachmentHashingService $service): int
    {
        $bar = $this->output->createProgressBar();

        $service->work(
            onStart: static function (int $total) use ($bar): void {
                $bar->setMaxSteps($total);
            },
            onProgress: static function () use ($bar): void {
                $bar->advance();
            }
        );

        $bar->finish();
        $this->newLine();

        return self::SUCCESS;
    }
}
