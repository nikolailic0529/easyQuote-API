<?php

namespace App\Console\Commands\Pipeliner;

use App\Services\Pipeliner\LinkedEntityAggregateService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class ListPlEntitiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'eq:list-pl-entities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List Pipeliner linked entities';

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
     */
    public function handle(LinkedEntityAggregateService $service): int
    {
        $rows = [];

        $flags = $this->resolveFlags();

        foreach ($service->aggregate($flags) as $entity) {
            $rows[] = [
                $entity->entityName,
                $entity->id,
                $entity->plReference,
                $entity->isValid === null
                    ? 'n/d'
                    : ($entity->isValid ? 'true' : 'false'),
            ];
        }

        $this->table(['Entity Name', 'Id', 'PL Reference', 'Is Valid'], $rows);

        return self::SUCCESS;
    }

    private function resolveFlags(): int
    {
        $flags = 0;

        if ($this->option('validate')) {
            $flags |= LinkedEntityAggregateService::VALIDATE_REFS;
        }

        return $flags;
    }

    protected function getOptions(): array
    {
        return [
            new InputOption(name: 'validate', mode: InputOption::VALUE_NONE, description: 'Whether to validate the references'),
        ];
    }
}
