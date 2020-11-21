<?php

namespace App\Console\Commands;

use App\DTO\RowsGroup;
use App\Models\Quote\BaseQuote;
use App\Models\Quote\Quote;
use App\Scopes\NonVersionScope;
use App\Scopes\QuoteTypeScope;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class UpdateQuoteGroupDescription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:update-quote-rowsgroups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Quote Rows Grouping';

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
        $query = Quote::query()
            ->withoutGlobalScopes()
            ->whereNotNull('group_description')
            ->withoutTrashed();


        $this->getOutput()->title('Updating Quote Rows Grouping...');
        $bar = $this->getOutput()->createProgressBar((clone $query)->count());

        DB::transaction(
            fn () => $query->chunk(100, fn (Collection $chunk) => $chunk->each(function (BaseQuote $quote) use ($bar) {
                $quote->group_description
                    ->filter(fn (RowsGroup $rowsGroup) => blank($rowsGroup->rows_ids))
                    ->each(function (RowsGroup $rowsGroup) use ($quote) {
                        $rows = $quote->getMappedRows(fn (Builder $query) => $query->where('group_name', $rowsGroup->name)->select('id'));

                        $rowsGroup->rows_ids = $rows->pluck('id')->toArray();
                    });

                $quote->timestamps = false;

                $quote->withoutEvents(fn () => $quote->save());

                $bar->advance();
            }))
        );

        $bar->finish();

        tap($this->getOutput())->newLine(1)->success('Quote Rows Grouping has been processed!');
    }
}
