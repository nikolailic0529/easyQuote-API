<?php

namespace App\Domain\ExchangeRate\Commands;

use App\Domain\ExchangeRate\Contracts\ManagesExchangeRates as Service;
use App\Domain\ExchangeRate\Repositories\RateFileRepository as Repository;
use App\Foundation\Log\Contracts\LoggerAware;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Log\LogManager;
use Symfony\Component\Console\Input\InputOption;

class UpdateExchangeRatesCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'eq:update-exchange-rates';

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
     */
    public function handle(Service $service, LogManager $logManager): int
    {
        if ($service instanceof LoggerAware) {
            $service->setLogger($logManager->stack(['stdout']));
        }

        // Load exchange rates from the defined file.
        if ($this->option('file')) {
            $filepath = $this->resolveFilepath();

            $date = $this->resolveRatesDate($filepath);

            $result = $service->updateRatesFromFile($filepath, $date);

            return $this->interpretUpdateResult($result);
        }

        // Load exchange rates from API.
        $result = $service->updateRates($this->resolveDateParameters());

        return $this->interpretUpdateResult($result);
    }

    protected function resolveDateParameters(): array
    {
        if (null === $this->option('month') && null === $this->option('year')) {
            return [];
        }

        if (null === $this->option('month')) {
            $dateOfYear = $this->resolveDateOfYear($this->option('year'));

            return CarbonPeriod::start($dateOfYear)
                ->end($dateOfYear->year === now()->year
                        ? now()->endOfMonth()
                        : $dateOfYear->endOfYear())
                ->step(\DateInterval::createFromDateString('1month'))
                ->toArray();
        }

        if (null === $this->option('year')) {
            return [$this->resolveDateOfMonth($this->option('month'))];
        }

        return [
            $this->resolveDateOfYear($this->option('year'))
                ->setMonth(
                    $this->resolveDateOfMonth($this->option('month'))->month
                ),
        ];
    }

    private function resolveDateOfMonth(string $month): CarbonImmutable
    {
        return Carbon::createFromFormat('!m', $month)
            ->setYear(now()->year)
            ->startOfMonth()
            ->toImmutable();
    }

    private function resolveDateOfYear(string $year): CarbonImmutable
    {
        $dateOfYear = strlen($year) < 4
            ? Carbon::createFromFormat('!y', $year)
            : Carbon::createFromFormat('!Y', $year);

        return $dateOfYear->startOfYear()->toImmutable();
    }

    /**
     * @throws \Exception
     */
    protected function resolveFilepath(): string
    {
        /** @var Repository */
        $repository = app(Repository::class);

        $names = $repository->getAllNames();

        if (empty($names)) {
            throw new \Exception(sprintf('No rate files found. Please put at least one at %s.', config('filesystems.disks.rates.root')));
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
        } catch (\Throwable $e) {
            /* Fallback when something is going wrong when fetch date from the file. */
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

    protected function getOptions(): array
    {
        return [
            new InputOption('file', mode: InputOption::VALUE_NONE, description: 'Load rates from a file'),
            new InputOption('month', shortcut: 'm', mode: InputOption::VALUE_REQUIRED, description: 'Load rates from remote for the month'),
            new InputOption('year', shortcut: 'y', mode: InputOption::VALUE_REQUIRED, description: 'Load rates from remote for the year'),
        ];
    }
}
