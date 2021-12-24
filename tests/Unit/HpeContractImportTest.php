<?php

namespace Tests\Unit;

use App\Contracts\Services\HpeContractState;
use App\DTO\HpeContract\HpeContractImportData;
use App\Models\HpeContract;
use App\Models\HpeContractFile;
use App\Services\HpeContractFileService;
use Illuminate\Http\Testing\File as TestingFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HpeContractImportTest extends TestCase
{
    /**
     * Test an application imports "20201210130642_39516_GB_S4.txt" file as hpe contract file.
     *
     * @return void
     */
    public function test_imports_20201210130642_39516_gb_s4_txt()
    {
        $filePath = static::contractFiles()['20201210130642_39516_GB_S4'];

        $file = static::createUploadedFile($filePath);

        /** @var HpeContractFileService $fileService */
        $fileService = app(HpeContractFileService::class);

        /** @var HpeContractFile */
        $hpeContractFile = $fileService->store($file);

        $response = $fileService->processImport($hpeContractFile, new HpeContractImportData([
            'date_format' => 'm/d/Y'
        ]));

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
            ->map(fn($asset) => Arr::only($asset, [
                'product_quantity',
                'product_number',
                'product_description',
                'serial_number',
                'support_start_date',
                'support_end_date',
                'support_account_reference',
                'contract_number',
            ]));

        $this->assertCount(3, $assets);

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

    /**
     * Test an application imports "20200928124822_123534_GB_S4.txt" file as hpe contract file.
     *
     * @return void
     */
    public function test_imports_20200928124822_123534_gb_s4_txt()
    {
        $this->authenticateApi();

        $filePath = static::contractFiles()['20200928124822_123534_GB_S4'];

        $file = static::createUploadedFile($filePath);

        /** @var HpeContractFileService $fileService */
        $fileService = app(HpeContractFileService::class);

        /** @var HpeContractFile $hpeContractFile */
        $hpeContractFile = $fileService->store($file);

        $response = $fileService->processImport($hpeContractFile, new HpeContractImportData([
            'date_format' => 'm/d/Y'
        ]));

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

    /**
     * Test an application imports "20201125122458_39516_GB_S4.txt" file as hpe contract file.
     *
     * @return void
     */
    public function test_imports_20201125122458_39516_gb_s4_txt()
    {
        $this->authenticateApi();

        $filePath = static::contractFiles()['20201125122458_39516_GB_S4'];

        $file = static::createUploadedFile($filePath);

        /** @var HpeContractFileService $fileService */
        $fileService = app(HpeContractFileService::class);

        /** @var HpeContractFile $hpeContractFile */
        $hpeContractFile = $fileService->store($file);

        $response = $fileService->processImport($hpeContractFile, new HpeContractImportData([
            'date_format' => 'm/d/Y'
        ]));

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
            ->map(fn($asset) => Arr::only($asset, [
                'product_quantity',
                'product_number',
                'product_description',
                'serial_number',
                'support_start_date',
                'support_end_date',
                'support_account_reference',
                'contract_number',
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

    /**
     * Test an application imports "20200817083029_123286_GB_S4.txt" file as hpe contract file.
     *
     * @return void
     */
    public function test_imports_20200817083029_123286_gb_s4_txt()
    {
        $this->authenticateApi();

        $filePath = static::contractFiles()['20200817083029_123286_GB_S4'];

        $file = static::createUploadedFile($filePath);

        /** @var HpeContractFileService $fileService */
        $fileService = app(HpeContractFileService::class);

        /** @var HpeContractFile */
        $hpeContractFile = $fileService->store($file);

        $response = $fileService->processImport($hpeContractFile, new HpeContractImportData([
            'date_format' => 'm/d/Y'
        ]));

        $this->assertFalse($response->failed());

        /** @var HpeContractState $stateProcessor */
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

        $stateProcessor->markAssetsAsSelected($hpeContract, $assetIds->all());

        $preview = $stateProcessor->retrieveSummarizedContractData($hpeContract);

        $this->assertSame($preview->service_overview[0]['contract_number'], '4000007072');
        $this->assertSame($preview->service_overview[0]['contract_start_date'], '26/08/2020');
        $this->assertSame($preview->service_overview[0]['contract_end_date'], '25/08/2021');
        $this->assertSame($preview->service_overview[0]['service_description_2'], 'Telefonische SW Unterstutzung');
    }

    /**
     * Test an application imports "20200623063555_123286_GB_S4.txt" file as hpe contract file.
     *
     * @return void
     */
    public function test_imports_20200623063555_123286_gb_s4_txt()
    {
        $this->authenticateApi();

        $contractFiles = static::contractFiles();

        /** @var HpeContractFileService $fileService */
        $fileService = app(HpeContractFileService::class);

        $file = static::createUploadedFile(Arr::get($contractFiles, '20200623063555_123286_GB_S4'));

        /** @var HpeContractFile $hpeContractFile */
        $hpeContractFile = $fileService->store($file);

        $this->assertInstanceOf(HpeContractFile::class, $hpeContractFile);

        $response = $fileService->processImport($hpeContractFile, new HpeContractImportData([
            'date_format' => 'm/d/Y'
        ]));

        $this->assertFalse($response->failed());

        $this->assertEquals(3, $hpeContractFile->hpeContractData()->count());

        $this->assertArrayHasEqualValues(optional($hpeContractFile->hpeContractData[0])->toArray(), [
            'amp_id' => '87-SMD502 703',
            'support_account_reference' => '1000834175_00001',
            'contract_number' => '4000003765',
            'order_authorization' => '471262142',
            'contract_start_date' => '2020-07-08',
            'contract_end_date' => '2023-07-07',
            'price' => 0.0,
            'product_number' => 'HA158AC',
            'serial_number' => null,
            'product_description' => 'Telefonische SW Unterstutzung',
            'product_quantity' => 1,
            'asset_type' => 'Software Support Service',
            'service_type' => 'K3 - HP Technology Softwa',
            'service_code' => null,
            'service_description' => null,
            'service_code_2' => 'BD505A',
            'service_description_2' => 'HP iLO Adv incl 3yr TS U 1-Svr Lic',
            'service_levels' => 'SW Technical Support;SW Electronic Support;24 Hrs Std Office Days;24 Hrs Day 6;24 Hrs Day 7;Holidays Covered;Standard Response',
            'hw_delivery_contact_name' => 'Cavin Jean Michel',
            'hw_delivery_contact_phone' => null,
            'sw_delivery_contact_name' => 'Cavin Jean Michel',
            'sw_delivery_contact_phone' => null,
            'pr_support_contact_name' => 'Cavin Jean Michel',
            'pr_support_contact_phone' => null,
            'customer_name' => 'TESEDI Schweiz GmbH',
            'customer_address' => 'Einkaufszentrum Glatt',
            'customer_city' => 'Wallisellen',
            'customer_post_code' => '8304',
            'customer_state_code' => 'ZE',
            'support_start_date' => '2020-08-07',
            'support_end_date' => '2023-07-07',
        ]);

        $this->assertArrayHasEqualValues(optional($hpeContractFile->hpeContractData[1])->toArray(), [
            'amp_id' => '87-SMD502 703',
            'support_account_reference' => '1000834175_00001',
            'contract_number' => '4000003765',
            'order_authorization' => '471262142',
            'contract_start_date' => '2020-07-08',
            'contract_end_date' => '2023-07-07',
            'price' => 0.0,
            'product_number' => 'BD505A',
            'serial_number' => '9YE5AJTUY4YE',
            'product_description' => 'HP iLO Adv incl 3yr TS U 1-Svr Lic',
            'product_quantity' => 1,
            'asset_type' => 'Software',
            'service_type' => '4U - Volume Software',
            'service_code' => 'HA156AC',
            'service_description' => 'HPE Software Updates SVC',
            'service_code_2' => 'HA158AC',
            'service_description_2' => 'Telefonische SW Unterstutzung',
            'service_levels' => 'SW Technical Support;SW Electronic Support;24 Hrs Std Office Days;24 Hrs Day 6;24 Hrs Day 7;Holidays Covered;Standard Response',
            'hw_delivery_contact_name' => 'Cavin Jean Michel',
            'hw_delivery_contact_phone' => null,
            'sw_delivery_contact_name' => 'Cavin Jean Michel',
            'sw_delivery_contact_phone' => null,
            'pr_support_contact_name' => 'Cavin Jean Michel',
            'pr_support_contact_phone' => null,
            'customer_name' => 'TESEDI Schweiz GmbH',
            'customer_address' => 'Einkaufszentrum Glatt',
            'customer_city' => 'Wallisellen',
            'customer_post_code' => '8304',
            'customer_state_code' => 'ZE',
            'support_start_date' => '2020-08-07',
            'support_end_date' => '2023-07-07',
        ]);

        $this->assertArrayHasEqualValues(optional($hpeContractFile->hpeContractData[2])->toArray(), [
            'amp_id' => '87-SMD502 703',
            'support_account_reference' => '1000834175_00001',
            'contract_number' => '4000003765',
            'order_authorization' => '471262142',
            'contract_start_date' => '2020-07-08',
            'contract_end_date' => '2023-07-07',
            'price' => 0.0,
            'product_number' => 'BD505A',
            'serial_number' => 'CEYAAJTUY4YE',
            'product_description' => 'HP iLO Adv incl 3yr TS U 1-Svr Lic',
            'product_quantity' => 1,
            'asset_type' => 'Software',
            'service_type' => '4U - Volume Software',
            'service_code' => 'HA156AC',
            'service_description' => 'HPE Software Updates SVC',
            'service_code_2' => 'HA158AC',
            'service_description_2' => 'Telefonische SW Unterstutzung',
            'service_levels' => 'SW Technical Support;SW Electronic Support;24 Hrs Std Office Days;24 Hrs Day 6;24 Hrs Day 7;Holidays Covered;Standard Response',
            'hw_delivery_contact_name' => 'Cavin Jean Michel',
            'hw_delivery_contact_phone' => null,
            'sw_delivery_contact_name' => 'Cavin Jean Michel',
            'sw_delivery_contact_phone' => null,
            'pr_support_contact_name' => 'Cavin Jean Michel',
            'pr_support_contact_phone' => null,
            'customer_name' => 'TESEDI Schweiz GmbH',
            'customer_address' => 'Einkaufszentrum Glatt',
            'customer_city' => 'Wallisellen',
            'customer_post_code' => '8304',
            'customer_state_code' => 'ZE',
            'support_start_date' => '2020-08-07',
            'support_end_date' => '2023-07-07',
        ]);
    }

    /**
     * Test an application imports "20210622105121_39516_GB_S4.txt" file as hpe contract file.
     *
     * @return void
     */
    public function test_imports_20210622105121_39516_gb_s4_txt()
    {
        /** @var HpeContractFileService $fileService */
        $fileService = $this->app[HpeContractFileService::class];

        /** @var HpeContractState $contractProcessor */
        $contractProcessor = $this->app[HpeContractState::class];

        $filePath = base_path('tests/Unit/Data/hpe-contract-test/20210622105121_39516_GB_S4.txt');

        $uploadedFile = UploadedFile::fake()->createWithContent('20210622105121_39516_GB_S4.txt', file_get_contents($filePath));

        /** @var HpeContractFile $hpeContractFile */
        $hpeContractFile = $fileService->store($uploadedFile);

        $this->assertInstanceOf(HpeContractFile::class, $hpeContractFile);

        $response = $fileService->processImport($hpeContractFile, new HpeContractImportData([
            'date_format' => 'm/d/Y'
        ]));

        $this->assertFalse($response->failed());

        $hpeContract = factory(HpeContract::class)->create();

        $response = $contractProcessor->processHpeContractData($hpeContract, $hpeContractFile);

        $this->assertTrue($response);

        $data = $contractProcessor->retrieveContractData($hpeContract);

        $this->assertCount(1, $data);
        $this->assertIsArray($data[0]);
        $this->assertArrayHasKey('assets', $data[0]);
        $this->assertCount(1, $data[0]['assets']);
    }

    /**
     * Test an application imports "20210804110434_39516_GB_S4.txt" file as hpe contract file.
     *
     * @return void
     */
    public function test_imports_20210804110434_39516_gb_s4_txt()
    {
        /** @var HpeContractFileService $fileService */
        $fileService = $this->app[HpeContractFileService::class];

        /** @var HpeContractState $contractProcessor */
        $contractProcessor = $this->app[HpeContractState::class];

        $filePath = base_path('tests/Unit/Data/hpe-contract-test/20210804110434_39516_GB_S4.txt');

        $uploadedFile = UploadedFile::fake()->createWithContent('20210804110434_39516_GB_S4.txt', file_get_contents($filePath));

        /** @var HpeContractFile $hpeContractFile */
        $hpeContractFile = $fileService->store($uploadedFile);

        $this->assertInstanceOf(HpeContractFile::class, $hpeContractFile);

        $response = $fileService->processImport($hpeContractFile, new HpeContractImportData([
            'date_format' => 'm/d/Y'
        ]));

        $this->assertFalse($response->failed());

        $hpeContract = factory(HpeContract::class)->create();

        $response = $contractProcessor->processHpeContractData($hpeContract, $hpeContractFile);

        $this->assertTrue($response);

        $data = $contractProcessor->retrieveContractData($hpeContract);

        $this->assertCount(1, $data);
        $this->assertIsArray($data[0]);
        $this->assertArrayHasKey('assets', $data[0]);
        $this->assertCount(192, $data[0]['assets']);
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