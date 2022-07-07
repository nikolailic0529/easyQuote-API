<?php

namespace App\Console\Commands\Pipeliner;

use App\Services\Pipeliner\LinkedEntityAggregateService;
use Illuminate\Console\Command;

class ListEntities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:list-pl-entities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
    public function handle(LinkedEntityAggregateService $service)
    {
        $rows = [];

        foreach ($service->aggregate() as $entity) {
            $rows[] = [
                $entity->entityName,
                $entity->id,
                $entity->plReference,
                $entity->isValid ? 'true' : 'false'
            ];
        }

        $this->table(['Entity Name', 'Id', 'PL Reference', 'Is Valid'], $rows);

        return self::SUCCESS;
    }
}
