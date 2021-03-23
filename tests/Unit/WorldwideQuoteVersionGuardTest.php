<?php

namespace Tests\Unit;

use App\Models\Opportunity;
use App\Models\OpportunitySupplier;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\QuoteFile\MappedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Models\QuoteFile\ScheduleData;
use App\Models\User;
use App\Services\WorldwideQuote\WorldwideQuoteVersionGuard;
use Tests\TestCase;

class WorldwideQuoteVersionGuardTest extends TestCase
{
    /**
     * Test the guard resolves new version of a quote for acting user,
     * when the acting user is not owner of the quote.
     *
     * @return void
     * @throws \Throwable
     */
    public function testResolvesNewVersionForActingUser()
    {
        $quoteOwner = factory(User::class)->create();

        /** @var Opportunity $opportunity */
        $opportunity = factory(Opportunity::class)->create();

        /** @var OpportunitySupplier $supplier */
        $supplier = factory(OpportunitySupplier::class)->create([
            'opportunity_id' => $opportunity->getKey()
        ]);

        /** @var WorldwideQuote $quote */
        $quote = factory(WorldwideQuote::class)->create([
            'user_id' => $quoteOwner->getKey(),
            'opportunity_id' => $opportunity->getKey()
        ]);

        $distributorFile = factory(QuoteFile::class)->create();

        /** @var QuoteFile $scheduleFile */
        $scheduleFile = factory(QuoteFile::class)->create();

        /** @var ScheduleData $scheduleFileData */
        $scheduleFileData = factory(ScheduleData::class)->create([
            'quote_file_id' => $scheduleFile->getKey()
        ]);

        $mappedRows = factory(MappedRow::class, 10)->create([
            'quote_file_id' => $distributorFile->getKey()
        ]);

        $mappedRowDictionary = $mappedRows->getDictionary();

        /** @var WorldwideDistribution $distributorQuote */
        $distributorQuote = factory(WorldwideDistribution::class)->create([
            'worldwide_quote_id' => $quote->getKey(),
            'opportunity_supplier_id' => $supplier->getKey(),
            'distributor_file_id' => $distributorFile->getKey(),
            'schedule_file_id' => $scheduleFile->getKey(),
        ]);

        /** @var User $actingUser */
        $actingUser = factory(User::class)->create();

        $versionGuard = new WorldwideQuoteVersionGuard($quote, $actingUser);

        /** @var WorldwideQuoteVersion $newVersion */
        $newVersion = $versionGuard->resolveModelForActingUser();

        $this->assertInstanceOf(WorldwideQuoteVersion::class, $newVersion);

        $this->assertTrue($newVersion->wasRecentlyCreated);

        $this->assertEquals($actingUser->getKey(), $newVersion->{$newVersion->user()->getForeignKeyName()});

        $this->assertCount(1, $newVersion->worldwideDistributions);

        /** @var WorldwideDistribution $newDistributorQuote */
        $newDistributorQuote = $newVersion->worldwideDistributions->first();

        $this->assertNotEquals($distributorQuote->getKey(), $newDistributorQuote->getKey());

        $this->assertNotEquals($distributorFile->getKey(), $newDistributorQuote->{$newDistributorQuote->distributorFile()->getForeignKeyName()});

        $this->assertNotEquals($scheduleFile->getKey(), $newDistributorQuote->{$newDistributorQuote->scheduleFile()->getForeignKeyName()});

        $this->assertCount($mappedRows->count(), $newDistributorQuote->mappedRows);

        foreach ($newDistributorQuote->mappedRows as $row) {
            $this->assertArrayHasKey($row->replicated_mapped_row_id, $mappedRowDictionary);

            $this->assertArrayNotHasKey($row->getKey(), $mappedRowDictionary);
        }

        $this->assertDatabaseHas('worldwide_quotes', [
            'id' => $quote->getKey(),
            'active_version_id' => $newVersion->getKey(),
            'deleted_at' => null
        ]);
    }
}
