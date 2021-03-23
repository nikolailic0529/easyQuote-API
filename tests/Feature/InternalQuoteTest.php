<?php

namespace Tests\Feature;

use App\DTO\RowsGroup;
use App\Models\Address;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer\Customer;
use App\Models\Quote\Quote;
use App\Models\QuoteFile\ImportedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

class InternalQuoteTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to create a customer for a new internal quote.
     *
     * @return void
     */
    public function testCanCreateCustomerForInternalQuote()
    {
        $this->authenticateApi();

        $this->postJson('api/quotes/customers', [

            "int_company_id" => Company::where('type', 'Internal')->value('id'),
            "customer_name" => "James Brown Ltd",
            "quotation_valid_until" => "2020-12-16",
            "support_start_date" => "2020-12-16",
            "support_end_date" => "2020-12-15",
            "invoicing_terms" => "Upfront",
            "vendors" => [
                Vendor::inRandomOrder()->value('id')
            ],
            "email" => "Customer@JBLTD.com",
            "vat" => "Exempt",
            "service_levels" => [
                [
                    "service_level" => "test"
                ]
            ],
            "addresses" => Address::limit(2)->pluck('id')->all(),
            "contacts" => Contact::limit(2)->pluck('id')->all()
        ])->assertCreated();
    }

    /**
     * Test an ability to create new rows group when the acting user is not the quote owner.
     *
     * @return void
     */
    public function testCanCreateNewRowsGroupWhenUserIsNotQuoteOwner()
    {
        $quoteOwner = factory(User::class)->create();
        $quoteCustomer = factory(Customer::class)->create();

        $distributorFile = factory(QuoteFile::class)->create(['imported_page' => 2]);
        $importedRows = factory(ImportedRow::class, 2)->create(['quote_file_id' => $distributorFile->getKey(), 'page' => 2]);

        $quote = factory(Quote::class)->create(['user_id' => $quoteOwner->getKey(), 'customer_id' => $quoteCustomer->getKey(), 'distributor_file_id' => $distributorFile->getKey()]);

        $this->authenticateApi($actingUser = factory(User::class)->create());

        $response = $this->postJson('api/quotes/groups/' . $quote->getKey(), [
            'name' => Str::random(),
            'search_text' => Str::random(),
            'rows' => $importedRows->modelKeys()
        ])
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'is_selected',
                'name',
                'rows_ids',
                'search_text'
            ]);

        $response = $this->getJson('api/quotes/groups/'.$quote->getKey());

        $expectedGroupRows = $importedRows->modelKeys();
        $actualGroupRows = $response->json('0.rows.*.replicated_row_id');

        $this->assertCount(count($expectedGroupRows), $actualGroupRows);

        sort($expectedGroupRows);
        sort($actualGroupRows);

        $this->assertEquals($expectedGroupRows, $actualGroupRows);
    }

    /**
     * Test an ability to update an existing rows group when the acting user is not the quote owner.
     *
     * @return void
     */
    public function testCanUpdateRowsGroupWhenUserIsNotQuoteOwner()
    {
        $quoteOwner = factory(User::class)->create();
        $quoteCustomer = factory(Customer::class)->create();

        $distributorFile = factory(QuoteFile::class)->create(['imported_page' => 2]);
        $importedRows = factory(ImportedRow::class, 2)->create(['quote_file_id' => $distributorFile->getKey(), 'page' => 2]);

        $quote = factory(Quote::class)->create([
            'user_id' => $quoteOwner->getKey(),
            'customer_id' => $quoteCustomer->getKey(),
            'distributor_file_id' => $distributorFile->getKey(),
            'group_description' => collect([
                $group = new RowsGroup([
                    'id' => (string) Str::uuid(),
                    'name' => Str::random(),
                    'search_text' => Str::random(),
                    'rows_ids' => $importedRows->modelKeys()
                ])
            ])
        ]);

        $this->authenticateApi($actingUser = factory(User::class)->create());

        $this->patchJson('api/quotes/groups/'.$quote->getKey().'/'.$group->id, [
            'name' => Str::random(),
            'search_text' => Str::random(),
            'rows' => $importedRows->modelKeys()
        ])
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->getJson('api/quotes/groups/'.$quote->getKey());

        $expectedGroupRows = $importedRows->modelKeys();
        $actualGroupRows = $response->json('0.rows.*.replicated_row_id');

        $this->assertCount(count($expectedGroupRows), $actualGroupRows);

        sort($expectedGroupRows);
        sort($actualGroupRows);

        $this->assertEquals($expectedGroupRows, $actualGroupRows);
    }


    /**
     * Test an ability to delete an existing rows group when the acting user is not the quote owner.
     *
     * @return void
     */
    public function testCanDeleteRowsGroupWhenUserIsNotQuoteOwner()
    {
        $quoteOwner = factory(User::class)->create();
        $quoteCustomer = factory(Customer::class)->create();

        $distributorFile = factory(QuoteFile::class)->create(['imported_page' => 2]);
        $importedRows = factory(ImportedRow::class, 2)->create(['quote_file_id' => $distributorFile->getKey(), 'page' => 2]);

        $quote = factory(Quote::class)->create([
            'user_id' => $quoteOwner->getKey(),
            'customer_id' => $quoteCustomer->getKey(),
            'distributor_file_id' => $distributorFile->getKey(),
            'group_description' => collect([
                $group = new RowsGroup([
                    'id' => (string) Str::uuid(),
                    'name' => Str::random(),
                    'search_text' => Str::random(),
                    'rows_ids' => $importedRows->modelKeys()
                ])
            ])
        ]);

        $this->authenticateApi($actingUser = factory(User::class)->create());

        $this->deleteJson('api/quotes/groups/'.$quote->getKey().'/'.$group->id)
            ->assertOk()
            ->assertExactJson([true]);

        $response = $this->getJson('api/quotes/groups/'.$quote->getKey())->assertOk();

        $this->assertEmpty($response->json());
    }

    /**
     * Test an ability to search rows of quote.
     *
     * @return void
     */
    public function testCanSearchQuoteRows()
    {
        $quoteOwner = factory(User::class)->create();
        $quoteCustomer = factory(Customer::class)->create();

        $distributorFile = factory(QuoteFile::class)->create(['imported_page' => 2]);
        $importedRows = factory(ImportedRow::class, 2)->create(['quote_file_id' => $distributorFile->getKey(), 'page' => 2]);

        $searchQuery = $importedRows->first()->columns_data->first()['value'];

        $quote = factory(Quote::class)->create([
            'user_id' => $quoteOwner->getKey(),
            'customer_id' => $quoteCustomer->getKey(),
            'distributor_file_id' => $distributorFile->getKey()
        ]);

        $this->authenticateApi($quoteOwner);

        $response = $this->postJson('api/quotes/step/2', [
            'quote_id' => $quote->getKey(),
            'search' => $searchQuery,
        ])
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'replicated_row_id', 'is_selected'
                ]
            ]);


        $this->assertNotEmpty($response->json());
    }
}
