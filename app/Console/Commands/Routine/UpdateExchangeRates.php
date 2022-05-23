<?php

namespace App\Console\Commands\Routine;

use Illuminate\Console\{
    Command,
    ConfirmableTrait,
};
use App\Contracts\Services\ManagesExchangeRates as Service;
use App\Repositories\RateFileRepository as Repository;
use Carbon\Carbon;
use Throwable;

class UpdateExchangeRates extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:update-exchange-rates {--file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Exchange Rates';

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
     * @param Service $service
     * @return int
     */
    public function handle(Service $service): int
    {
        // Load exchange rates from the defined file.
        if ($this->option('file')) {
            $filepath = $this->resolveFilepath();

            $date = $this->resolveRatesDate($filepath);

            $result = $service->updateRatesFromFile($filepath, $date);

            return $this->interpretUpdateResult($result);
        }

        // Load exchange rates from API.
        $result = $service->updateRates();

        return $this->interpretUpdateResult($result);
    }

    protected function resolveFilepath()
    {
        /** @var Repository */
        $repository = app(Repository::class);

        $names = $repository->getAllNames();

        if (empty($names)) {
            $this->warn(sprintf('No rate files found. Please put at least one at %s.', config('filesystems.disks.rates.root')));
        }

        $name = $this->choice('Which file?', $names, 0);

        return $repository->path($name);
    }

    protected function resolveRatesDate(string $filepath): Carbon
    {
        /** @var Service */
        $service = app(Service::class);

        try {
            $date = $service->parseRatesDateFromFile($filepath);

            return tap(
                $date,
                fn (Carbon $date) => $this->info(sprintf(ER_DT_01, $date->format('M Y')))
            );
        } catch (Throwable $e) {
            /** Fallback when something is going wrong when fetch date from the file. */
            $this->warn(ER_DT_ERR_01);

            return tap(
                $this->askRatesPeriod(),
                fn (Carbon $date) => $this->info(sprintf(ER_DT_01, $date->format('M Y')))
            );
        }
    }

    protected function askRatesPeriod(): Carbon
    {
        $month = $this->ask('Please enter month number', now()->format('m'));
        $month = sprintf('%02d', $month);

        $year = $this->ask('Please enter year number', now()->format('y'));
        $year = str_pad(substr($year, 0, 4), 4, 20, STR_PAD_LEFT);

        return Carbon::create($year, $month);
    }

    protected function interpretUpdateResult($result): int
    {
        if ($result) {
            $this->info('Exchange Rates were successfully updated!');
            return self::SUCCESS;
        }

        $this->error('Something went wrong when Exchange Rates updating.');
        return self::FAILURE;
    }
}
