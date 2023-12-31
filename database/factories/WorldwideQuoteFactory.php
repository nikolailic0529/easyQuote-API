<?php

namespace Database\Factories;

use App\Domain\User\Models\User;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

class WorldwideQuoteFactory extends Factory
{
    protected $model = WorldwideQuote::class;

    public function configure(): WorldwideQuoteFactory
    {
        return $this->afterCreating(function (WorldwideQuote $quote) {
            $quote->activeVersion->worldwideQuote()->associate($quote);
            $quote->activeVersion->save();
        });
    }

    public function definition(): array
    {
        $sequenceNumber = DB::table('worldwide_quotes')->max('sequence_number');
        $newNumber = $sequenceNumber + 1;

        return [
            'active_version_id' => WorldwideQuoteVersion::factory(),
            'contract_type_id' => CT_CONTRACT,
            'user_id' => User::factory(),
            'opportunity_id' => Opportunity::factory(),
            'sequence_number' => $newNumber,
            'quote_number' => sprintf("EPD-WW-DP-%'.07d", $newNumber),
        ];
    }
}
