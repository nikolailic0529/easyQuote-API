<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Contracts\Services\HpeContractState;
use App\Models\HpeContract;
use App\Models\HpeContractFile;
use App\Models\Template\HpeContractTemplate;
use App\Services\HpeContractExporter;
use App\Services\HpeContractFileService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\{Arr, Str};
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Testing\File as TestingFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * @group build
 */
class HpeContractTest extends TestCase
{
    use WithFaker, DatabaseTransactions;

    /**
     * Test HPE Contract File Uploading.
     *
     * @return void
     */
    public function testHpeContractFileUploading()
    {
        $this->authenticateApi();

        $file = static::createUploadedFile(Arr::random(static::contractFiles()));

        $response = $this->postJson('api/hpe-contract-files', ['file' => $file])
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'original_file_name',
                'original_file_path',
                'user_id',
                'created_at',
                'updated_at'
            ]);

        $this->assertEquals($response->json('original_file_name'), $file->name);

        Storage::disk('hpe_contract_files')->assertExists($response->json('original_file_path'));
    }

    public function testHpeContract_20201210130642_39516_GB_S4_Import()
    {
        $filePath = static::contractFiles()['20201210130642_39516_GB_S4'];

        $file = static::createUploadedFile($filePath);
        
        /** @var HpeContractFileService */
        $fileService = app(HpeContractFileService::class);

        /** @var HpeContractFile */
        $hpeContractFile = $fileService->store($file);

        $response = $fileService->processImport($hpeContractFile);

        $this->assertFalse($response->failed());

        /** @var HpeContractState */
        $stateProcessor = app(HpeContractState::class);

        /** @var HpeContract */
        $hpeContract = factory(HpeContract::class)->create();

        $this->assertTrue(
            $stateProcessor->processHpeContractData($hpeContract, $hpeContractFile, $response)
        );

        $data = $stateProcessor->retrieveContractData($hpeContract);

        $assets = $data->pluck('assets')->collapse()
            ->map(fn ($asset) => Arr::only($asset, [
                'product_quantity',
                'product_number',
                'product_description',
                'serial_number',
                'support_start_date',
                'support_end_date',
                'support_account_reference',
                'contract_number'
            ]));

        $this->assertCount(1, $assets);

        $this->assertContainsEquals([
            'product_quantity' => 1,
            'product_number' => 'H1SR4AS',
            'product_description' => 'HPE Service Credit',
            'support_start_date' => '01/12/2020',
            'support_end_date' => '30/11/2025',
            'support_account_reference' => '1000460771_00028',
            'contract_number' => '4000029201',
            'serial_number' => null,
        ], $assets);
    }

    public function test20200928124822123534GBS4Importing()
    {
        $this->authenticateApi();

        $filePath = static::contractFiles()['20200928124822_123534_GB_S4'];

        $file = static::createUploadedFile($filePath);

        /** @var HpeContractFileService */
        $fileService = app(HpeContractFileService::class);

        /** @var HpeContractFile */
        $hpeContractFile = $fileService->store($file);

        $response = $fileService->processImport($hpeContractFile);

        $this->assertFalse($response->failed());

        /** @var HpeContractState */
        $stateProcessor = app(HpeContractState::class);

        /** @var HpeContract */
        $hpeContract = factory(HpeContract::class)->create();

        $this->assertTrue(
            $stateProcessor->processHpeContractData($hpeContract, $hpeContractFile, $response)
        );

        $serviceDescription = $hpeContract->services->pluck('service_description_2');

        $this->assertContains("HPE Software Updates SVC", $serviceDescription);
        $this->assertContains("HPE Software Technical Unlimited Support", $serviceDescription);
    }

    public function testHpeContract2020112512245839516GBS4Importing()
    {
        $this->authenticateApi();

        $filePath = static::contractFiles()['20201125122458_39516_GB_S4'];

        $file = static::createUploadedFile($filePath);

        /** @var HpeContractFileService */
        $fileService = app(HpeContractFileService::class);

        /** @var HpeContractFile */
        $hpeContractFile = $fileService->store($file);

        $response = $fileService->processImport($hpeContractFile);

        $this->assertFalse($response->failed());

        /** @var HpeContractState */
        $stateProcessor = app(HpeContractState::class);

        /** @var HpeContract */
        $hpeContract = factory(HpeContract::class)->create();

        $this->assertTrue(
            $stateProcessor->processHpeContractData($hpeContract, $hpeContractFile, $response)
        );

        $data = $stateProcessor->retrieveContractData($hpeContract);

        $assets = $data->pluck('assets')->collapse()
            ->map(fn ($asset) => Arr::only($asset, [
                'product_quantity',
                'product_number',
                'product_description',
                'serial_number',
                'support_start_date',
                'support_end_date',
                'support_account_reference',
                'contract_number'
            ]));

        $this->assertCount(3, $assets);

        $this->assertContainsEquals([
            "product_quantity" => 1,
            "product_number" => "J9821A",
            "product_description" => "HP 5406R zl2 Switch",
            "serial_number" => "SG05G490G3",
            "support_start_date" => "28/10/2020",
            "support_end_date" => "27/10/2025",
            "support_account_reference" => "1000819644_00003",
            "contract_number" => "4000024672",
        ], $assets);

        $this->assertContainsEquals([
            "product_quantity" => 1,
            "product_number" => "J9821A",
            "product_description" => "HP 5406R zl2 Switch",
            "serial_number" => "SG05G490G3",
            "support_start_date" => "28/10/2020",
            "support_end_date" => "27/10/2025",
            "support_account_reference" => "1000819644_00003",
            "contract_number" => "4000024672",
        ], $assets);

        $this->assertContainsEquals([
            "product_quantity" => 1,
            "product_number" => "J9821A",
            "product_description" => "HP 5406R zl2 Switch",
            "serial_number" => "SG05G490G3",
            "support_start_date" => "28/10/2020",
            "support_end_date" => "27/10/2025",
            "support_account_reference" => "1000819644_00003",
            "contract_number" => "4000024672",
        ], $assets);
    }

    public function testHpeContract20200817083029123286GBS4Importing()
    {
        $this->authenticateApi();

        $filePath = static::contractFiles()['20200817083029_123286_GB_S4'];

        $file = static::createUploadedFile($filePath);

        /** @var HpeContractFileService */
        $fileService = app(HpeContractFileService::class);

        /** @var HpeContractFile */
        $hpeContractFile = $fileService->store($file);

        $response = $fileService->processImport($hpeContractFile);

        $this->assertFalse($response->failed());

        /** @var HpeContractState */
        $stateProcessor = app(HpeContractState::class);

        /** @var HpeContract */
        $hpeContract = factory(HpeContract::class)->create();

        $this->assertTrue(
            $stateProcessor->processHpeContractData($hpeContract, $hpeContractFile, $response)
        );

        $this->assertSame($hpeContract->sold_contact->org_name, 'smart dynamic ag');
        $this->assertSame($hpeContract->bill_contact->org_name, 'smart dynamic ag');
        $this->assertSame($hpeContract->sold_contact->address, 'Zentweg 9');
        $this->assertSame($hpeContract->bill_contact->address, 'Zentweg 9');
        $this->assertSame($hpeContract->sold_contact->post_code, '3006');
        $this->assertSame($hpeContract->bill_contact->post_code, '3006');
        $this->assertSame($hpeContract->sold_contact->city, 'Bern');
        $this->assertSame($hpeContract->bill_contact->city, 'Bern');

        $this->assertSame($hpeContract->entitled_party_contact->org_name, 'Semadeni (Europe) AG');
        $this->assertSame($hpeContract->end_customer_contact->org_name, 'Semadeni (Europe) AG');
        $this->assertSame($hpeContract->entitled_party_contact->address, 'Tagetlistrasse 35-39');
        $this->assertSame($hpeContract->end_customer_contact->address, 'Tagetlistrasse 35-39');
        $this->assertSame($hpeContract->entitled_party_contact->post_code, '3072');
        $this->assertSame($hpeContract->end_customer_contact->post_code, '3072');
        $this->assertSame($hpeContract->entitled_party_contact->city, 'Ostermundigen');
        $this->assertSame($hpeContract->end_customer_contact->city, 'Ostermundigen');

        $assetIds = Collection::wrap($stateProcessor->retrieveContractData($hpeContract))->pluck('assets.*.id')->collapse();

        $stateProcessor->selectAssets($hpeContract, $assetIds->all());

        $preview = $stateProcessor->retrieveSummarizedContractData($hpeContract);

        $this->assertSame($preview->service_overview[0]['contract_number'], '4000007072');
        $this->assertSame($preview->service_overview[0]['contract_start_date'], '26/08/2020');
        $this->assertSame($preview->service_overview[0]['contract_end_date'], '25/08/2021');
        $this->assertSame($preview->service_overview[0]['service_description_2'], 'Telefonische SW Unterstutzung');
    }

    /**
     * Test HPE Contract File importing.
     *
     * @return void
     */
    public function testHpeContract20200623063555123286GBS4Importing()
    {
        $this->authenticateApi();

        $contractFiles = static::contractFiles();

        /** @var HpeContractFileService */
        $fileService = app(HpeContractFileService::class);

        $file = static::createUploadedFile(Arr::get($contractFiles, '20200623063555_123286_GB_S4'));

        /** @var HpeContractFile */
        $hpeContractFile = $fileService->store($file);

        $this->assertInstanceOf(HpeContractFile::class, $hpeContractFile);

        $response = $fileService->processImport($hpeContractFile);

        $this->assertFalse($response->failed());

        $this->assertEquals(3, $hpeContractFile->hpeContractData()->count());

        $this->assertArrayHasEqualValues(optional($hpeContractFile->hpeContractData[0])->toArray(), [
            'amp_id'                    => '87-SMD502 703',
            'support_account_reference' => '1000834175_00001',
            'contract_number'           => '4000003765',
            'order_authorization'       => '471262142',
            'contract_start_date'       => '2020-07-08',
            'contract_end_date'         => '2023-07-07',
            'price'                     => 0.0,
            'product_number'            => 'HA158AC',
            'serial_number'             => null,
            'product_description'       => 'Telefonische SW Unterstutzung',
            'product_quantity'          => 1,
            'asset_type'                => 'Software Support Service',
            'service_type'              => 'K3 - HP Technology Softwa',
            'service_code'              => null,
            'service_description'       => null,
            'service_code_2'            => 'BD505A',
            'service_description_2'     => 'HP iLO Adv incl 3yr TS U 1-Svr Lic',
            'service_levels'            => 'SW Technical Support;SW Electronic Support;24 Hrs Std Office Days;24 Hrs Day 6;24 Hrs Day 7;Holidays Covered;Standard Response',
            'hw_delivery_contact_name'  => 'Cavin Jean Michel',
            'hw_delivery_contact_phone' => null,
            'sw_delivery_contact_name'  => 'Cavin Jean Michel',
            'sw_delivery_contact_phone' => null,
            'pr_support_contact_name'   => 'Cavin Jean Michel',
            'pr_support_contact_phone'  => null,
            'customer_name'             => 'TESEDI Schweiz GmbH',
            'customer_address'          => 'Einkaufszentrum Glatt',
            'customer_city'             => 'Wallisellen',
            'customer_post_code'        => '8304',
            'customer_state_code'       => 'ZE',
            'support_start_date'        => '2020-08-07',
            'support_end_date'          => '2023-07-07',
        ]);

        $this->assertArrayHasEqualValues(optional($hpeContractFile->hpeContractData[1])->toArray(), [
            'amp_id'                    => '87-SMD502 703',
            'support_account_reference' => '1000834175_00001',
            'contract_number'           => '4000003765',
            'order_authorization'       => '471262142',
            'contract_start_date'       => '2020-07-08',
            'contract_end_date'         => '2023-07-07',
            'price'                     => 0.0,
            'product_number'            => 'BD505A',
            'serial_number'             => '9YE5AJTUY4YE',
            'product_description'       => 'HP iLO Adv incl 3yr TS U 1-Svr Lic',
            'product_quantity'          => 1,
            'asset_type'                => 'Software',
            'service_type'              => '4U - Volume Software',
            'service_code'              => 'HA156AC',
            'service_description'       => 'HPE Software Updates SVC',
            'service_code_2'            => 'HA158AC',
            'service_description_2'     => 'Telefonische SW Unterstutzung',
            'service_levels'            => 'SW Technical Support;SW Electronic Support;24 Hrs Std Office Days;24 Hrs Day 6;24 Hrs Day 7;Holidays Covered;Standard Response',
            'hw_delivery_contact_name'  => 'Cavin Jean Michel',
            'hw_delivery_contact_phone' => null,
            'sw_delivery_contact_name'  => 'Cavin Jean Michel',
            'sw_delivery_contact_phone' => null,
            'pr_support_contact_name'   => 'Cavin Jean Michel',
            'pr_support_contact_phone'  => null,
            'customer_name'             => 'TESEDI Schweiz GmbH',
            'customer_address'          => 'Einkaufszentrum Glatt',
            'customer_city'             => 'Wallisellen',
            'customer_post_code'        => '8304',
            'customer_state_code'       => 'ZE',
            'support_start_date'        => '2020-08-07',
            'support_end_date'          => '2023-07-07',
        ]);

        $this->assertArrayHasEqualValues(optional($hpeContractFile->hpeContractData[2])->toArray(), [
            'amp_id'                    => '87-SMD502 703',
            'support_account_reference' => '1000834175_00001',
            'contract_number'           => '4000003765',
            'order_authorization'       => '471262142',
            'contract_start_date'       => '2020-07-08',
            'contract_end_date'         => '2023-07-07',
            'price'                     => 0.0,
            'product_number'            => 'BD505A',
            'serial_number'             => 'CEYAAJTUY4YE',
            'product_description'       => 'HP iLO Adv incl 3yr TS U 1-Svr Lic',
            'product_quantity'          => 1,
            'asset_type'                => 'Software',
            'service_type'              => '4U - Volume Software',
            'service_code'              => 'HA156AC',
            'service_description'       => 'HPE Software Updates SVC',
            'service_code_2'            => 'HA158AC',
            'service_description_2'     => 'Telefonische SW Unterstutzung',
            'service_levels'            => 'SW Technical Support;SW Electronic Support;24 Hrs Std Office Days;24 Hrs Day 6;24 Hrs Day 7;Holidays Covered;Standard Response',
            'hw_delivery_contact_name'  => 'Cavin Jean Michel',
            'hw_delivery_contact_phone' => null,
            'sw_delivery_contact_name'  => 'Cavin Jean Michel',
            'sw_delivery_contact_phone' => null,
            'pr_support_contact_name'   => 'Cavin Jean Michel',
            'pr_support_contact_phone'  => null,
            'customer_name'             => 'TESEDI Schweiz GmbH',
            'customer_address'          => 'Einkaufszentrum Glatt',
            'customer_city'             => 'Wallisellen',
            'customer_post_code'        => '8304',
            'customer_state_code'       => 'ZE',
            'support_start_date'        => '2020-08-07',
            'support_end_date'          => '2023-07-07',
        ]);
    }

    /**
     * Test HPE Contract Initiating on the first step.
     *
     * @return void
     */
    public function testHpeContractInitiating()
    {
        $this->authenticateApi();

        $response = $this->postJson('api/hpe-contracts', [
            'last_drafted_step' => 'Initiated'
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
                'customer_contacts'          => [
                    'sold_contact'           => ['org_name', 'attn', 'email', 'phone', 'address', 'post_code', 'country', 'city'],
                    'bill_contact'           => ['org_name', 'attn', 'email', 'phone', 'address', 'post_code', 'country', 'city'],
                    'hw_delivery_contact'    => ['org_name', 'attn', 'email', 'phone', 'address', 'post_code', 'country', 'city'],
                    'sw_delivery_contact'    => ['org_name', 'attn', 'email', 'phone', 'address', 'post_code', 'country', 'city'],
                    'pr_support_contact'     => ['org_name', 'attn', 'email', 'phone', 'address', 'post_code', 'country', 'city'],
                    'entitled_party_contact' => ['org_name', 'attn', 'email', 'phone', 'address', 'post_code', 'country', 'city'],
                    'end_customer_contact'   => ['org_name', 'attn', 'email', 'phone', 'address', 'post_code', 'country', 'city'],
                ],
                'last_drafted_step',
                'additional_notes',
                'completeness',
                'checkbox_status',
                'contract_date',
            ]);

        $id = $response->json('id');

        $this->assertDatabaseHas('hpe_contracts', ['id' => $id, 'deleted_at' => null]);
    }

    /**
     * Test a newly created HPE Contract Deleting.
     * Deletion of the HPE Contract must trigger deleting its' reflection on the quotes table.
     *
     * @return void
     */
    public function testHpeContractDeleting()
    {
        $this->authenticateApi();

        /** @var HpeContractState */
        $processor = app(HpeContractState::class);

        $hpeContract = $processor->processState(['last_drafted_step' => 'Initiated']);

        $this->deleteJson('api/hpe-contracts/' . $hpeContract->getKey())->assertOk();

        $this->assertSoftDeleted($hpeContract);
    }

    /**
     * Test a newly created deactivated HPE Contract Activating.
     * Activation of the HPE Contract must trigger activating its' reflection on the quotes table.
     *
     * @return void
     */
    public function testHpeContractActivating()
    {
        $this->authenticateApi();

        /** @var HpeContractState */
        $processor = app(HpeContractState::class);

        $hpeContract = $processor->processState(['last_drafted_step' => 'Initiated']);

        $processor->deactivate($hpeContract);

        $this->patchJson('api/hpe-contracts/' . $hpeContract->getKey() . '/activate')->assertOk();

        $this->assertNotNull($hpeContract->refresh()->activated_at);
    }


    /**
     * Test a newly created activated HPE Contract Deactivating.
     * Deactivation of the HPE Contract must trigger deactivating its' reflection on the quotes table.
     *
     * @return void
     */
    public function testHpeContractDeactivating()
    {
        $this->authenticateApi();

        /** @var HpeContractState */
        $processor = app(HpeContractState::class);

        $hpeContract = $processor->processState(['last_drafted_step' => 'Initiated']);

        $processor->activate($hpeContract);

        $this->patchJson('api/hpe-contracts/' . $hpeContract->getKey() . '/deactivate')->assertOk();

        $this->assertNull($hpeContract->refresh()->activated_at);
    }

    /**
     * Test exporting a newly created HPE Contract.
     *
     * @return void
     */
    public function testHpeContractExporting()
    {
        $this->authenticateApi();

        /** @var HpeContract */
        $hpeContract = factory(HpeContract::class)->create([
            'purchase_order_no' => $this->faker->randomNumber()
        ]);

        $response = $this->get('api/hpe-contracts/' . $hpeContract->getKey() . '/export')->assertOk();

        $response->assertHeader('content-disposition', 'attachment; filename=' . $hpeContract->purchase_order_no . '.pdf');
    }

    /**
     * Test replicating a newly created submitted HPE Contract.
     *
     * @return void
     */
    public function testHpeContractReplicating()
    {
        $this->authenticateApi();

        /** @var HpeContractState */
        $processor = app(HpeContractState::class);

        $hpeContract = $processor->processState(['last_drafted_step' => 'Initiated']);

        $hpeContract->forceFill(['submitted_at' => now(), 'activated_at' => now()])->save();

        DB::table('hpe_contracts')->whereNull('submitted_at')->delete();
        $this->assertDatabaseMissing('hpe_contracts', ['submitted_at' => null]);

        $this->put('api/hpe-contracts/'.$hpeContract->getKey().'/copy')->assertOk();

        $this->assertDatabaseHas('hpe_contracts', ['id' => $hpeContract->getKey(), 'activated_at' => null, ['submitted_at', '!=', null]]);
        $this->assertDatabaseHas('hpe_contracts', [['activated_at', '!=', null], 'submitted_at' => null ]);
    }

    protected static function createUploadedFile(string $filePath): TestingFile
    {
        return UploadedFile::fake()->createWithContent(File::basename($filePath), File::get($filePath));
    }

    protected static function contractFiles(): array
    {
        return [
            '20200623063555_123286_GB_S4' => base_path('tests/Unit/Data/hpe-contract-test/20200623063555_123286_GB_S4.txt'),
            '20200626124903_123286_GB_S4' => base_path('tests/Unit/Data/hpe-contract-test/20200626124903_123286_GB_S4.txt'),
            '20200626125911_123286_GB_S4' => base_path('tests/Unit/Data/hpe-contract-test/20200626125911_123286_GB_S4.txt'),
            '20200817083029_123286_GB_S4' => base_path('tests/Unit/Data/hpe-contract-test/20200817083029_123286_GB_S4.txt'),
            '20200928124822_123534_GB_S4' => base_path('tests/Unit/Data/hpe-contract-test/20200928124822_123534_GB_S4.txt'),
            '20201125122458_39516_GB_S4' => base_path('tests/Unit/Data/hpe-contract-test/20201125122458_39516_GB_S4.txt'),
            '20201210130642_39516_GB_S4' => base_path('tests/Unit/Data/hpe-contract-test/20201210130642_39516_GB_S4.txt'),
        ];
    }
}
