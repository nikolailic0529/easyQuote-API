<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Quote\Quote;
use Illuminate\Support\Collection;

class FreshQuotesGroupDescription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:fresh-quotes-groups';

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
    public function handle()
    {
        $query = Quote::whereNotNull('group_description')
            ->whereRaw('group_description->"$[0].is_selected" is null');

        $bar = $this->output->createProgressBar((clone $query)->count());

        $query->cursor()->each(function ($quote) use ($bar) {
            $quote->timestamps;

            $groups = Collection::wrap($quote->group_description)->map(fn ($group) => array_merge($group, ['is_selected' => true]));

            $quote->update(['group_description' => $groups]);

            $bar->advance();
        });

        $bar->finish();
        $this->output->newLine(2);
    }
}
