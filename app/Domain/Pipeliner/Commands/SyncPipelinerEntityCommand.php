<?php

namespace App\Domain\Pipeliner\Commands;

use App\Domain\Appointment\Models\Appointment;
use App\Domain\Company\Models\Company;
use App\Domain\Note\Models\Note;
use App\Domain\Pipeliner\Services\PipelinerDataSyncService;
use App\Domain\Task\Models\Task;
use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class SyncPipelinerEntityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'eq:sync-pl-entity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform synchronization of particular Pipeliner entity';

    protected static array $classMap = [
        'Opportunity' => Opportunity::class,
        'Company' => Company::class,
        'Note' => Note::class,
        'Appointment' => Appointment::class,
        'Task' => Task::class,
        'User' => User::class,
    ];

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
     * @throws \Illuminate\Http\Client\RequestException
     */
    public function handle(PipelinerDataSyncService $service, LogManager $logManager): int
    {
        $logger = $this->resolveLogger($logManager);

        $correlationId = (string) Str::orderedUuid();

        $service
            ->setCauser($this->resolveCauser())
            ->setLogger($logger)
            ->setFlags($this->resolveSyncMethods())
            ->setCorrelation($correlationId);

        if ($this->option('queue')) {
            $result = $service->queueModelSync($this->resolveModel());

            $rows = collect($result['queued'])->map(static fn (array $item): array => [$item['strategy']]);

            $this->getOutput()->horizontalTable(['Strategy'], $rows->all());
        } else {
            $result = $service->syncModel($this->resolveModel());

            $rows = collect($result['applied'])->map(static fn (array $item): array => [
                $item['strategy'],
                $item['ok'] ? 'true' : 'false',
                isset($item['errors']) ? json_encode($item['errors']) : '',
            ]);

            $this->getOutput()->horizontalTable(['Strategy', 'Ok', 'Errors'], $rows->all());
        }

        return self::SUCCESS;
    }

    protected function resolveModel(): Model
    {
        $type = $this->argument('model-type');

        if (!key_exists($type, static::$classMap)) {
            throw new \InvalidArgumentException(sprintf('Unsupported model type provided: `%s`. Allowed: %s.', $type, collect(static::$classMap)->keys()->map(static fn (string $v) => "$v")->join(', ')));
        }

        /** @var Model $model */
        $model = new (static::$classMap[$type]);

        return $model->newQuery()->findOrFail($this->argument('model-id'));
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

        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return User::query()->findOrFail($this->option('user-id'));
    }

    protected function resolveSyncMethods(): int
    {
        $value = collect($this->option('method'))->map(static fn (string $v): string => \mb_strtolower($v));

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
            throw new \InvalidArgumentException('No valid method has been provided. Consider using any of: `push`, `pull`.');
        }

        return $options;
    }

    protected function getArguments(): array
    {
        return [
            new InputArgument('model-id', InputArgument::REQUIRED, description: 'The model id to sync'),
            new InputArgument('model-type', InputArgument::REQUIRED, description: 'The model type to sync'),
        ];
    }

    protected function getOptions(): array
    {
        return [
            new InputOption(name: 'user-id', mode: InputOption::VALUE_REQUIRED, description: 'The acting user id'),
            new InputOption(name: 'method', mode: InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                description: 'Method to sync (pull/push)'),
            new InputOption(name: 'queue', mode: InputOption::VALUE_NONE | InputOption::VALUE_NEGATABLE,
                description: 'Whether to queue sync or not'),
        ];
    }
}
