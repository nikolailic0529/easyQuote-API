<?php

namespace App\Domain\Address\Commands;

use App\Domain\Address\Models\Address;
use App\Domain\Address\Services\ValidateAddressService;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;
use Symfony\Component\Console\Helper\ProgressBar;

class ValidateAddressesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:validate-addresses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate the existing addresses';

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
     */
    public function handle(LogManager $logManager, ValidateAddressService $addressService): int
    {
        $logManager->setDefaultDriver('addresses');

        $logger = $logManager->stack(['addresses']);

        $bar = $this->output->createProgressBar();

        ProgressBar::setFormatDefinition('minimal', '%current%/%max%');

        $bar->setFormat('minimal');

        $addressService->setLogger($logger)->work(
            onStart: static function (int $max) use ($bar): void {
                $bar->start($max);
            },
            onProgress: static function (bool $result, Address $address) use ($bar): void {
                $bar->advance();
            }
        );

        $bar->finish();
        $this->newLine();

        return self::SUCCESS;
    }
}
