<?php

namespace Tests\Unit;

use App\Contracts\Services\ProcessesWorldwideQuoteState;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\OpportunitySupplier;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteNote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\MappedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Models\QuoteFile\ScheduleData;
use App\Models\Template\TemplateField;
use App\Models\User;
use App\Services\WorldwideQuote\WorldwideQuoteVersionGuard;
use Illuminate\Support\Str;
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

        $quoteNote = tap(new WorldwideQuoteNote, function (WorldwideQuoteNote $note) use ($quote) {
            $note->worldwideQuote()->associate($quote);
            $note->worldwideQuoteVersion()->associate($quote->activeVersion);
            $note->text = Str::random(40);

            $note->save();
        });

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
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'opportunity_supplier_id' => $supplier->getKey(),
            'distributor_file_id' => $distributorFile->getKey(),
            'schedule_file_id' => $scheduleFile->getKey(),
        ]);

        $templateFields = TemplateField::query()->whereIn('name', $this->app['config']['quote-mapping.worldwide_quote.fields'])->get();

        $distributorQuote->templateFields()->sync(
            $templateFields
        );

        /** @var DistributionRowsGroup $rowsGroup */
        $rowsGroup = factory(DistributionRowsGroup::class)->create([
            'worldwide_distribution_id' => $distributorQuote->getKey()
        ]);

        $rowsGroup->rows()->attach($mappedRows);

        $distributorQuoteAddresses = factory(Address::class, 2)->create();
        $distributorQuoteContacts = factory(Contact::class, 2)->create();

        $distributorQuoteAddressDictionary = $distributorQuoteAddresses->getDictionary();
        $distributorQuoteContactDictionary = $distributorQuoteContacts->getDictionary();

        $distributorQuote->addresses()->sync($distributorQuoteAddresses);
        $distributorQuote->contacts()->sync($distributorQuoteContacts);

        /** @var User $actingUser */
        $actingUser = factory(User::class)->create();

        $newVersion = $this->app->make(WorldwideQuoteVersionGuard::class)->resolveModelForActingUser($quote, $actingUser);

        $this->assertDatabaseHas('worldwide_quotes', [
            'id' => $quote->getKey(),
            'active_version_id' => $newVersion->getKey(),
            'deleted_at' => null
        ]);

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

        $this->assertCount($templateFields->count(), $newDistributorQuote->mapping);

        foreach ($newDistributorQuote->mappedRows as $row) {
            $this->assertArrayHasKey($row->replicated_mapped_row_id, $mappedRowDictionary);

            $this->assertArrayNotHasKey($row->getKey(), $mappedRowDictionary);
        }

        foreach ($newDistributorQuote->addresses as $address) {
            $this->assertArrayHasKey($address->getKey(), $distributorQuoteAddressDictionary);
        }

        foreach ($newDistributorQuote->contacts as $contact) {
            $this->assertArrayHasKey($contact->getKey(), $distributorQuoteContactDictionary);
        }

        $this->assertNotEmpty($newDistributorQuote->rowsGroups);
    }

    /**
     * Test replicates active version of worldwide quote entity and creates new worldwide quote entity.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function testReplicatesWorldwideQuoteEntity()
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
            'worldwide_quote_id' => $quote->activeVersion->getKey(),
            'worldwide_quote_type' => $quote->activeVersion->getMorphClass(),
            'opportunity_supplier_id' => $supplier->getKey(),
            'distributor_file_id' => $distributorFile->getKey(),
            'schedule_file_id' => $scheduleFile->getKey(),
        ]);

        $templateFields = TemplateField::query()->whereIn('name', $this->app['config']['quote-mapping.worldwide_quote.fields'])->get();

        $distributorQuote->templateFields()->sync(
            $templateFields
        );

        /** @var DistributionRowsGroup $rowsGroup */
        $rowsGroup = factory(DistributionRowsGroup::class)->create([
            'worldwide_distribution_id' => $distributorQuote->getKey()
        ]);

        $rowsGroup->rows()->attach($mappedRows);

        $distributorQuoteAddresses = factory(Address::class, 2)->create();
        $distributorQuoteContacts = factory(Contact::class, 2)->create();

        $distributorQuoteAddressDictionary = $distributorQuoteAddresses->getDictionary();
        $distributorQuoteContactDictionary = $distributorQuoteContacts->getDictionary();

        $distributorQuote->addresses()->sync($distributorQuoteAddresses);
        $distributorQuote->contacts()->sync($distributorQuoteContacts);

        /** @var User $actingUser */
        $actingUser = factory(User::class)->create();

        /** @var WorldwideQuote $newQuote */
        $newQuote = $this->app[ProcessesWorldwideQuoteState::class]->processQuoteReplication($quote, $actingUser);

        $this->assertInstanceOf(WorldwideQuote::class, $newQuote);

        $this->assertNotSame($quote->quote_number, $newQuote->quote_number);

        $newDistributorQuote = $newQuote->activeVersion->worldwideDistributions->first();

        $this->assertEquals($actingUser->getKey(), $newQuote->user()->getParentKey());

        $this->assertCount(1, $newQuote->activeVersion->worldwideDistributions);

        /** @var WorldwideDistribution $newDistributorQuote */
        $newDistributorQuote = $newQuote->activeVersion->worldwideDistributions->first();

        $this->assertNotEquals($distributorQuote->getKey(), $newDistributorQuote->getKey());

        $this->assertNotEquals($distributorFile->getKey(), $newDistributorQuote->{$newDistributorQuote->distributorFile()->getForeignKeyName()});

        $this->assertNotEquals($scheduleFile->getKey(), $newDistributorQuote->{$newDistributorQuote->scheduleFile()->getForeignKeyName()});

        $this->assertCount($mappedRows->count(), $newDistributorQuote->mappedRows);

        $this->assertCount($templateFields->count(), $newDistributorQuote->mapping);

        foreach ($newDistributorQuote->mappedRows as $row) {
            $this->assertArrayHasKey($row->replicated_mapped_row_id, $mappedRowDictionary);

            $this->assertArrayNotHasKey($row->getKey(), $mappedRowDictionary);
        }

        foreach ($newDistributorQuote->addresses as $address) {
            $this->assertArrayHasKey($address->getKey(), $distributorQuoteAddressDictionary);
        }

        foreach ($newDistributorQuote->contacts as $contact) {
            $this->assertArrayHasKey($contact->getKey(), $distributorQuoteContactDictionary);
        }

        $this->assertNotEmpty($newDistributorQuote->rowsGroups);
    }
}
