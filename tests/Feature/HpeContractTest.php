<?php

namespace Tests\Feature;

use App\Domain\HpeContract\Models\HpeContract;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * @group build
 */
class HpeContractTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->faker);
    }

    /**
     * Test an ability to initiate a new hpe contract.
     *
     * @return void
     */
    public function testCanInitiateHpeContract()
    {
        $this->authenticateApi();

        $response = $this->postJson('api/hpe-contracts', [
            'last_drafted_step' => 'Initiated',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'created_at',
                'updated_at',
                'submitted_at',
                'hpe_contract_number',
                'user_id',
                'quote_template_id',
                'company_id',
                'country_id',
                'hpe_contract_file_id',
                'amp_id',
                'support_account_reference',
                'orders_authorization',
                'contract_numbers',
                'services',
                'customer_name',
                'customer_address',
                'customer_city',
                'customer_post_code',
                'customer_country_code',
                'purchase_order_no',
                'hpe_sales_order_no',
                'purchase_order_date',
                'customer_contacts' => [
                    'sold_contact' => ['org_name', 'attn', 'email', 'phone', 'address', 'post_code', 'country', 'city'],
                    'bill_contact' => ['org_name', 'attn', 'email', 'phone', 'address', 'post_code', 'country', 'city'],
                    'hw_delivery_contact' => ['org_name', 'attn', 'email', 'phone', 'address', 'post_code', 'country', 'city'],
                    'sw_delivery_contact' => ['org_name', 'attn', 'email', 'phone', 'address', 'post_code', 'country', 'city'],
                    'pr_support_contact' => ['org_name', 'attn', 'email', 'phone', 'address', 'post_code', 'country', 'city'],
                    'entitled_party_contact' => ['org_name', 'attn', 'email', 'phone', 'address', 'post_code', 'country', 'city'],
                    'end_customer_contact' => ['org_name', 'attn', 'email', 'phone', 'address', 'post_code', 'country', 'city'],
                ],
                'last_drafted_step',
                'additional_notes',
                'completeness',
                'checkbox_status',
                'contract_date',
            ]);
    }

    /**
     * Test an ability to upload HPE Contract file.
     *
     * @return void
     */
    public function testCanUploadHpeContractFile()
    {
        $this->authenticateApi();

        $file = UploadedFile::fake()->createWithContent('20200623063555_123286_GB_S4.txt', file_get_contents(base_path('tests/Unit/Data/hpe-contract-test/20200623063555_123286_GB_S4.txt')));

        $response = $this->postJson('api/hpe-contract-files', ['file' => $file])
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'original_file_name',
                'original_file_path',
                'user_id',
                'created_at',
                'updated_at',
            ]);

        $this->assertEquals($response->json('original_file_name'), $file->name);

        Storage::disk('hpe_contract_files')->assertExists($response->json('original_file_path'));
    }

    /**
     * Test an ability to import hpe contract file.
     *
     * @return void
     */
    public function testCanImportHpeContractFile()
    {
        $this->authenticateApi();

        $response = $this->postJson('api/hpe-contracts?'.Arr::query(['include' => 'hpe_contract_file']), [
            'last_drafted_step' => 'Initiated',
        ])
            ->assertOk()
            ->assertJsonStructure([
                'id',
            ]);

        $contractKey = $response->json('id');

        $file = UploadedFile::fake()->createWithContent('20200623063555_123286_GB_S4.txt', file_get_contents(base_path('tests/Unit/Data/hpe-contract-test/20200623063555_123286_GB_S4.txt')));

        $response = $this->postJson('api/hpe-contract-files', ['file' => $file])
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'original_file_name',
                'original_file_path',
                'user_id',
                'created_at',
                'updated_at',
            ]);

        $fileKey = $response->json('id');

        $this->patchJson("api/hpe-contracts/{$contractKey}/import/{$fileKey}", [
            'date_format' => 'd/m/Y',
        ])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/hpe-contracts/'.$contractKey.'?'.Arr::query(['include' => 'hpe_contract_file']))
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'hpe_contract_file' => [
                    'id',
                    'date_format',
                ],
            ]);

        $this->assertEquals('d/m/Y', $response->json('hpe_contract_file.date_format'));
    }

    /**
     * Test an ability to delete an existing hpe contract.
     *
     * @return void
     */
    public function testCanDeleteHpeContract()
    {
        $this->authenticateApi();

        $response = $this->postJson('api/hpe-contracts', [
            'last_drafted_step' => 'Initiated',
        ])
            ->assertOk()
            ->assertJsonStructure(['id']);

        $contractKey = $response->json('id');

        $this->deleteJson('api/hpe-contracts/'.$contractKey)
            ->assertOk();

        $this->getJson('api/hpe-contracts/'.$contractKey)
            ->assertNotFound();
    }

    /**
     * Test an ability to activate an existing hpe contract.
     *
     * @return void
     */
    public function testHpeContractActivating()
    {
        $this->authenticateApi();

        $response = $this->postJson('api/hpe-contracts', [
            'last_drafted_step' => 'Initiated',
        ])
            ->assertOk()
            ->assertJsonStructure(['id', 'activated_at']);

        $this->assertNotEmpty($response->json('activated_at'));

        $contractKey = $response->json('id');

        $this->patchJson('api/hpe-contracts/'.$contractKey.'/deactivate')->assertOk();

        $response = $this->getJson('api/hpe-contracts/'.$contractKey)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));

        $this->patchJson('api/hpe-contracts/'.$contractKey.'/activate')->assertOk();

        $response = $this->getJson('api/hpe-contracts/'.$contractKey)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at',
            ]);

        $this->assertNotEmpty($response->json('activated_at'));
    }

    /**
     * Test an ability to deactivate an existing hpe contract.
     *
     * @return void
     */
    public function testCanDeactivateHpeContract()
    {
        $this->authenticateApi();

        $response = $this->postJson('api/hpe-contracts', [
            'last_drafted_step' => 'Initiated',
        ])
            ->assertOk()
            ->assertJsonStructure(['id', 'activated_at']);

        $this->assertNotEmpty($response->json('activated_at'));

        $contractKey = $response->json('id');

        $this->patchJson('api/hpe-contracts/'.$contractKey.'/deactivate')->assertOk();

        $response = $this->getJson('api/hpe-contracts/'.$contractKey)
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'activated_at',
            ]);

        $this->assertEmpty($response->json('activated_at'));
    }

    /**
     * Test an ability to export an existing hpe contract.
     *
     * @return void
     */
    public function testCanExportHpeContract()
    {
        $this->authenticateApi();

        /** @var HpeContract */
        $hpeContract = factory(HpeContract::class)->create([
            'purchase_order_no' => $this->faker->randomNumber(),
        ]);

        $this->getJson('api/hpe-contracts/'.$hpeContract->getKey().'/export')
            ->assertOk()
            ->assertHeader('content-disposition', 'attachment; filename='.$hpeContract->purchase_order_no.'.pdf');
    }

    /**
     * Test an ability to replicate an existing HPE Contract.
     *
     * @return void
     */
    public function testCanReplicateHpeContract()
    {
        $this->authenticateApi();

        $response = $this->postJson('api/hpe-contracts', [
            'last_drafted_step' => 'Initiated',
        ])
            ->assertOk()
            ->assertJsonStructure(['id']);

        $contractKey = $response->json('id');

        $this->put('api/hpe-contracts/'.$contractKey.'/copy')->assertOk();
    }
}
