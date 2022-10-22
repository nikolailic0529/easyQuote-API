<?php

namespace App\Console\Commands;

use App\Console\Commands\Pipeliner\TerminatePlSyncCommand;
use App\Models\User;
use App\Services\Pipeliner\PipelinerDataSyncService;
use App\Services\Pipeliner\SyncPipelinerDataStatus;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\SignalRegistry\SignalRegistry;

class SyncPipelinerData extends Command implements SignalableCommandInterface
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'eq:sync-pl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform synchronization of data from Pipeliner';

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
     * @param PipelinerDataSyncService $service
     * @param LogManager $logManager
     * @return int
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function handle(
        SyncPipelinerDataStatus $status,
        PipelinerDataSyncService $service,
        LogManager $logManager
    ): int
    {
        if ($this->option('status-owner')) {
            $status->setOwner($this->option('status-owner'));
        }

        $logger = $this->resolveLogger($logManager);

        $correlationId = (string)Str::orderedUuid();

        if (!$status->acquire()) {
            $logger->warning("Sync status: could not acquire.");

            return self::FAILURE;
        }

        try {
            $service
                ->setCauser($this->resolveCauser())
                ->setLogger($logger)
                ->setFlags($this->resolveSyncMethods())
                ->setStrategyFilter($this->resolveStrategyFilter())
                ->setCorrelation($correlationId)
                ->sync();
        } finally {
            $status->release();
        }

        return self::SUCCESS;
    }

    protected function resolveLogger(LogManager $logManager): LoggerInterface
    {
        if ($this->option('quiet')) {
            return $logManager->channel('pipeliner');
        }

        return $logManager->stack(['stdout', 'pipeliner']);
    }

    protected function resolveCauser(): ?User
    {
        if (is_null($this->option('user-id'))) {
            return null;
        }

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return User::query()->findOrFail($this->option('user-id'));
    }

    protected function resolveSyncMethods(): int
    {
        $value = collect($this->option('method'))->map(static fn(string $v): string => mb_strtolower($v));

        $options = 0;

        if ($value->isEmpty()) {
            return PipelinerDataSyncService::PUSH | PipelinerDataSyncService::PULL;
        }

        if ($value->contains('push')) {
            $options |= PipelinerDataSyncService::PUSH;
        }

        if ($value->contains('pull')) {
            $options |= PipelinerDataSyncService::PULL;
        }

        if (0 === $options) {
            throw new \InvalidArgumentException("No valid method has been provided. Consider using any of: `push`, `pull`.");
        }

        return $options;
    }

    protected function resolveStrategyFilter(): callable
    {
        $strategiesOpt = collect($this->option('strategy'));

        if ($strategiesOpt->isEmpty()) {
            $strategiesOpt->push(...config('pipeliner.sync.default_strategies'));
        }

        $nameStrategyMap = config('pipeliner.sync.strategies');

        $strategiesOpt->transform(static function (string $classOrName) use ($nameStrategyMap): string {
            if (class_exists($classOrName)) {
                return $classOrName;
            }

            if (false === isset($nameStrategyMap[$classOrName])) {
                throw new \InvalidArgumentException("Unsupported strategy `$classOrName`.");
            }

            return $nameStrategyMap[$classOrName];
        });

        return static function (object $strategy) use ($strategiesOpt): bool {
            foreach ($strategiesOpt as $classname) {
                if ($strategy instanceof $classname) {
                    return true;
                }
            }

            return false;
        };
    }

    protected function getOptions(): array
    {
        return [
            new InputOption(name: 'user-id', mode: InputOption::VALUE_REQUIRED, description: 'The acting user id'),
            new InputOption(name: 'method', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, description: 'Method to sync (pull/push)'),
            new InputOption(name: 'strategy', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, description: 'Strategy to sync'),
            new InputOption(name: 'status-owner', mode: InputOption::VALUE_REQUIRED, description: 'Sync status owner'),
        ];
    }

    public function getSubscribedSignals(): array
    {
        if (\defined('SIGINT') && SignalRegistry::isSupported()) {
            return [\SIGINT];
        }

        return [];
    }

    public function handleSignal(int $signal): void
    {
        if (!\defined('SIGINT') || !SignalRegistry::isSupported()) {
            return;
        }

        if (\SIGINT === $signal) {
            $status = $this->laravel->make(SyncPipelinerDataStatus::class);

            if ($status->running()) {
                $status->release();

                $this->line('Shutting down...');
            }
        }
    }
}
