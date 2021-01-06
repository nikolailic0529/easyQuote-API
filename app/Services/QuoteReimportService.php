<?php

namespace App\Services;

use App\Models\Quote\Quote;
use Illuminate\Support\Str;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Auth;
use App\Services\Concerns\WithOutput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Contracts\Services\ManagesDocumentProcessors as Parser;
use App\Services\Concerns\WithProgress;
use Closure;

class QuoteReimportService
{
    use WithOutput;

    protected Parser $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function reimport(string $id)
    {
        $quotes = $this->resolveQuotes($id);

        if ($quotes->isEmpty()) {
            throw new ModelNotFoundException("No Quote found by ID/RFQ $id");
        }

        $quotes->each(Closure::fromCallable([$this, 'processQuote']));

        return $quotes->count();
    }

    protected function processQuote(Quote $quote)
    {
        $RFQNumber = $quote->customer->rfq;

        customlog(['message' => "Importing Quote $RFQNumber..."]);
        $this->whenHasOutput(fn (OutputStyle $output) => $output->writeln("<fg=yellow;options=bold>Quote:</> <options=bold>$RFQNumber</>"));

        $version = $quote->activeVersionOrCurrent;

        Auth::guard()->setUser($version->user);

        customlog(['message' => "Acting as {$version->user->email} user"]);
        $this->whenHasOutput(fn (OutputStyle $output) => $output->writeln("<fg=yellow;options=bold>Acting as user:</> <options=bold>{$version->user->email}</>"));

        if ($version->priceList->exists) {
            customlog(['message' => "Distributor Price File exists. Try to import..."]);
            $this->whenHasOutput(fn (OutputStyle $output) => $output->writeln("Distributor Price File exists. Try to import..."));

            $this->parser->forwardProcessor($version->priceList);
            $this->parser->mapColumnsToFields($quote, $version->priceList);

            customlog(['message' => "Distributor Price File has been imported."]);
           
            $this->whenHasOutput(fn (OutputStyle $output) => $output->writeln("Distributor Price File imported."));
        }

        if ($version->paymentSchedule->exists) {
            customlog(['message' => 'Payment Schedule File exists. Try to import...']);
            $this->whenHasOutput(fn (OutputStyle $output) => $output->writeln("Payment Schedule File exists. Try to import..."));

            $this->parser->forwardProcessor($version->paymentSchedule);

            customlog(['message' => 'Payment Schedule File has been imported.']);
            $this->whenHasOutput(fn (OutputStyle $output) => $output->writeln("Payment Schedule File has been imported."));
        }

        $this->whenHasOutput(fn (OutputStyle $output) => $output->newLine(1));
    }

    protected function resolveQuotes(string $id): Collection
    {
        if (Str::isUuid($id)) {
            return Quote::whereKey($id)->get();
        }

        return Quote::whereHas('customer', fn (Builder $query) => $query->where('rfq', $id))->get();
    }
}
