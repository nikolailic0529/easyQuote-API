<?php

namespace App\Domain\Rescue\Services;

use App\Domain\DocumentProcessing\Contracts\ManagesDocumentProcessors as DocumentProcessor;
use App\Domain\Rescue\Contracts\QuoteState;
use App\Domain\Rescue\Models\Quote;
use App\Foundation\Console\Concerns\WithOutput;
use Illuminate\Console\OutputStyle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class QuoteReimportService
{
    use WithOutput;

    public function __construct(protected DocumentProcessor $documentProcessor,
                                protected QuoteState $quoteStateProcessor)
    {
    }

    public function performReimportOfQuote(string $id): int
    {
        $quotes = $this->resolveQuotes($id);

        if ($quotes->isEmpty()) {
            throw new ModelNotFoundException("No Quote found by ID/RFQ $id");
        }

        $quotes->each(\Closure::fromCallable([$this, 'processQuote']));

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
            customlog(['message' => 'Distributor Price File exists. Try to import...']);
            $this->whenHasOutput(fn (OutputStyle $output) => $output->writeln('Distributor Price File exists. Try to import...'));

            $this->documentProcessor->forwardProcessor($version->priceList);
            $this->quoteStateProcessor->guessQuoteMapping($quote);

            customlog(['message' => 'Distributor Price File has been imported.']);

            $this->whenHasOutput(fn (OutputStyle $output) => $output->writeln('Distributor Price File imported.'));
        }

        if ($version->paymentSchedule->exists) {
            customlog(['message' => 'Payment Schedule File exists. Try to import...']);
            $this->whenHasOutput(fn (OutputStyle $output) => $output->writeln('Payment Schedule File exists. Try to import...'));

            $this->documentProcessor->forwardProcessor($version->paymentSchedule);

            customlog(['message' => 'Payment Schedule File has been imported.']);
            $this->whenHasOutput(fn (OutputStyle $output) => $output->writeln('Payment Schedule File has been imported.'));
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
