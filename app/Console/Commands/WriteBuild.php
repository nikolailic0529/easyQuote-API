<?php

namespace App\Console\Commands;

use App\Contracts\Repositories\System\BuildRepositoryInterface as Build;
use Illuminate\Console\Command;

class WriteBuild extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:write-build {build_number} {git_tag}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Write Jekins Build in the storage';

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
    public function handle(Build $build)
    {
        $build_number = $this->argument('build_number');
        $git_tag = $this->argument('git_tag');

        $build = $build->firstOrCreate(compact('build_number', 'git_tag'));

        $this->info("Build with Number '{$build_number}' has been successfully stored!");
    }
}
