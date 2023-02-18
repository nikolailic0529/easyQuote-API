<?php

namespace Tests\Feature;

use App\Domain\Authentication\Services\UserTeamGate;
use App\Domain\Authorization\Models\Role;
use App\Domain\Company\Models\Company;
use App\Domain\Country\Models\Country;
use App\Domain\Team\Models\Team;
use App\Domain\User\Models\User;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Worldwide\Models\SalesOrderTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * @group build
 */
class SalesOrderTemplateTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->faker);
    }

    /**
     * Test an ability to view paginated sales order templates.
     *
     * @return void
     */
    public function testCanViewPaginatedSalesOrderTemplates()
    {
        $this->authenticateApi();

        factory(SalesOrderTemplate::class, 10)->create();

        $this->getJson('api/sales-order-templates')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'name',
                        'is_system',
                        'company_name',
                        'vendor_name',
                        'country_names',
                        'created_at',
                    ],
                ],
                'links' => [
                    'first', 'last', 'prev', 'next',
                ],
                'meta' => [
                    'current_page', 'from', 'last_page', 'links', 'path', 'per_page', 'to', 'total',
                ],
            ]);
    }

    /**
     * Test an ability to view template form of an existing sales order template.
     *
     * @return void
     */
    public function testCanViewTemplateFormOfSalesOrderTemplate()
    {
        $template = factory(SalesOrderTemplate::class)->create();

        $this->authenticateApi();

        $this->getJson('api/sales-order-templates/'.$template->getKey().'/form')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'first_page' => [
                    '*' => [
                        'id', 'label', 'is_image',
                    ],
                ],
                'data_pages' => [
                    '*' => [
                        'id', 'label', 'is_image',
                    ],
                ],
                'payment_schedule' => [
                    '*' => [
                        'id', 'label', 'is_image',
                    ],
                ],
            ]);
    }

    /**
     * Test an ability to create a new sales order template.
     *
     * @return void
     */
    public function testCanCreateSalesOrderTemplate()
    {
        $this->authenticateApi();

        $this->postJson('api/sales-order-templates', [
            'name' => $this->faker->text(191),
            'business_division_id' => BD_WORLDWIDE,
            'contract_type_id' => CT_CONTRACT,
            'company_id' => Company::query()->where('flags', '&', Company::SYSTEM)->value('id'),
            'vendor_id' => Vendor::query()->where('is_system', true)->value('id'),
            'countries' => Country::query()->limit(2)->pluck('id'),
        ])
//            ->dump()
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'name',
                'business_division_id',
                'contract_type_id',
                'company_id',
                'vendor_id',
            ]);
    }

    /**
     * Test an ability to update data of sales order template.
     *
     * @return void
     */
    public function testCanUpdateSalesOrderTemplate()
    {
        $template = factory(SalesOrderTemplate::class)->create();

        $this->authenticateApi();

        $this->patchJson('api/sales-order-templates/'.$template->getKey(), $data = [
            'name' => $this->faker->text(191),
            'business_division_id' => BD_WORLDWIDE,
            'contract_type_id' => CT_CONTRACT,
            'company_id' => Company::query()->where('flags', '&', Company::SYSTEM)->value('id'),
            'vendor_id' => Vendor::query()->where('is_system', true)->value('id'),
            'countries' => Country::query()->limit(2)->pluck('id'),
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'business_division_id',
                'contract_type_id',
                'company_id',
                'vendor_id',
                'countries',
                'form_data',
                'data_headers',
                'data_headers_keyed',
            ]);

        $this->getJson('api/sales-order-templates/'.$template->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'business_division_id',
                'contract_type_id',
                'company_id',
                'vendor_id',
                'countries',
                'form_data',
                'data_headers',
                'data_headers_keyed',
            ]);
    }

    /**
     * Test an ability to update schema of sales order template.
     *
     * @return void
     */
    public function testCanUpdateSchemaOfSalesOrderTemplate()
    {
        $template = factory(SalesOrderTemplate::class)->create();

        $this->authenticateApi();

        $this->patchJson('api/sales-order-templates/'.$template->getKey().'/schema', $data = [
            'form_data' => json_decode("{\"last_page\": [{\"id\": \"0a6a998b-8ca6-4c97-bc0a-c361c2865c3a\", \"name\": \"Single Column\", \"child\": [{\"id\": \"c107fd65-e092-4262-9fec-7f4df0fb4d18\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"7f311cee-2193-406d-ab17-f64165ce63e2\", \"css\": null, \"src\": null, \"name\": \"h-1\", \"show\": false, \"type\": \"h\", \"class\": \"blue bold\", \"label\": \"Heading\", \"value\": \"Thank you for choosing Support Warehouse to deliver your HPE Services Contract\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"c107fd65-e092-4262-9fec-7f4df0fb4d18\", \"attached_element_id\": \"0a6a998b-8ca6-4c97-bc0a-c361c2865c3a\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"18fb9d24-7d72-4e95-8ccc-08da7b0ab0f6\", \"name\": \"Single Column\", \"child\": [{\"id\": \"c107fd65-e092-4262-9fec-7f4df0fb4d18\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"9f4969ee-afa5-47bc-8711-65eab8bf44c2\", \"css\": null, \"src\": null, \"name\": \"richtext-1\", \"show\": false, \"type\": \"richtext\", \"class\": null, \"label\": \"Rich Text\", \"value\": \"<p>Your account manager will now help you to manage your services contract going forward, and will arrange quarterly support reviews for you.</p><p>Now that you're set up with your services contract, remember to let your account manager know when you purchase new hardware, so we can add it to the contract. This will help to minimize your support administration going forward.</p><p>Your account manager can also help if you need any assistance accessing the HPE Support Center or getting started with the proprietary tools.</p><p>When it comes to renewal, we'll notify you 45 to 90 days in advance to make sure you've got enough time to renew the current support.</p><p>You'll also receive our Support Services Update, intended to highlight any changes or updates regarding IT in general, Hewlett Packard Enterprise and Support Warehouse.</p><p><br></p>\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"c107fd65-e092-4262-9fec-7f4df0fb4d18\", \"attached_element_id\": \"18fb9d24-7d72-4e95-8ccc-08da7b0ab0f6\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"fe9b6ba0-d8c1-479e-a4cf-6e04d47d2805\", \"name\": \"Single Column\", \"child\": [{\"id\": \"c107fd65-e092-4262-9fec-7f4df0fb4d18\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"7f311cee-2193-406d-ab17-f64165ce63e2\", \"css\": null, \"src\": null, \"name\": \"h-1\", \"show\": false, \"type\": \"h\", \"class\": \"blue bold\", \"label\": \"Heading\", \"value\": \"Accessing HPE Support\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"c107fd65-e092-4262-9fec-7f4df0fb4d18\", \"attached_element_id\": \"fe9b6ba0-d8c1-479e-a4cf-6e04d47d2805\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"204684ed-51ee-4c9e-971b-4a558b776564\", \"name\": \"Single Column\", \"child\": [{\"id\": \"c107fd65-e092-4262-9fec-7f4df0fb4d18\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"9f4969ee-afa5-47bc-8711-65eab8bf44c2\", \"css\": null, \"src\": null, \"name\": \"richtext-1\", \"show\": false, \"type\": \"richtext\", \"class\": null, \"label\": \"Rich Text\", \"value\": \"<p>To access the proactive deliverables included in your Proactive Care services contract, you'll need to access Proactive Care Central.&nbsp;To get started, visit:</p><p><a href=\\\"http://www8.hp.com/be/en/business-services/proactive-support/proactive-care-central.html\\\" rel=\\\"noopener noreferrer\\\" target=\\\"_blank\\\">http://www8.hp.com/be/en/business-services/proactive-support/proactive-care-central.html</a></p><p><br></p><p>If you require support from HPE, please call 0900-1170000.</p><p>Alternatively, you can log a support call through your HPE Support Center account.</p><p>For all of the contact options, visit: <a href=\\\"http://www8.hp.com/us/en/hpe/contact/support.html\\\" rel=\\\"noopener noreferrer\\\" target=\\\"_blank\\\">http://www8.hp.com/us/en/hpe/contact/support.html</a></p>\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"c107fd65-e092-4262-9fec-7f4df0fb4d18\", \"attached_element_id\": \"204684ed-51ee-4c9e-971b-4a558b776564\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}], \"data_pages\": [{\"id\": \"cfc672cd-7729-4f45-bc15-2697155a8fbc\", \"name\": \"Single Column\", \"child\": [{\"id\": \"db99da75-9203-40f1-bfde-8cdf825b957e\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"22819812-231e-48ec-8483-3f93575ef952\", \"css\": null, \"src\": null, \"name\": \"h-1\", \"show\": false, \"type\": \"h\", \"class\": \"blue bold\", \"label\": \"Heading\", \"value\": \"Your HPE Services Contract\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"db99da75-9203-40f1-bfde-8cdf825b957e\", \"attached_element_id\": \"cfc672cd-7729-4f45-bc15-2697155a8fbc\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"49ae6176-d991-4d65-9209-58e9d97a2565\", \"name\": \"Four Column\", \"child\": [{\"id\": \"1efddc40-3eb5-4574-adb2-4a5c08f88a75\", \"class\": \"col-lg-3\", \"controls\": [{\"id\": \"c3fcddc7-46a5-4709-a905-a45adf94f356\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"Equipment Address:\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"1efddc40-3eb5-4574-adb2-4a5c08f88a75\", \"attached_element_id\": \"49ae6176-d991-4d65-9209-58e9d97a2565\"}], \"position\": 1}, {\"id\": \"1e3156d8-da84-4242-9fc1-45c0e03dfd87\", \"class\": \"col-lg-3\", \"controls\": [{\"id\": \"equipment_address\", \"css\": null, \"name\": \"equipment_address\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Equipment Address\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"1e3156d8-da84-4242-9fc1-45c0e03dfd87\", \"attached_element_id\": \"49ae6176-d991-4d65-9209-58e9d97a2565\"}], \"position\": 2}, {\"id\": \"408f3279-9e95-4684-82f9-9f26bc1b7f47\", \"class\": \"col-lg-3\", \"controls\": [{\"id\": \"c3fcddc7-46a5-4709-a905-a45adf94f356\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"Software Update Address:\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"408f3279-9e95-4684-82f9-9f26bc1b7f47\", \"attached_element_id\": \"49ae6176-d991-4d65-9209-58e9d97a2565\"}], \"position\": 3}, {\"id\": \"b7570c58-f90e-45af-9cd9-0f414d039ef2\", \"class\": \"col-lg-3 border-right\", \"controls\": [{\"id\": \"software_address\", \"css\": null, \"name\": \"software_address\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Software Address\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"b7570c58-f90e-45af-9cd9-0f414d039ef2\", \"attached_element_id\": \"49ae6176-d991-4d65-9209-58e9d97a2565\"}], \"position\": 4}], \"class\": \"four-column field-dragger\", \"order\": 5, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"4\"}, {\"id\": \"9d0b4e6a-6a23-402f-8c10-0207e5d4b503\", \"name\": \"Four Column\", \"child\": [{\"id\": \"1efddc40-3eb5-4574-adb2-4a5c08f88a75\", \"class\": \"col-lg-3\", \"controls\": [{\"id\": \"c3fcddc7-46a5-4709-a905-a45adf94f356\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"Hardware Contract:\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"1efddc40-3eb5-4574-adb2-4a5c08f88a75\", \"attached_element_id\": \"9d0b4e6a-6a23-402f-8c10-0207e5d4b503\"}], \"position\": 1}, {\"id\": \"1e3156d8-da84-4242-9fc1-45c0e03dfd87\", \"class\": \"col-lg-3\", \"controls\": [{\"id\": \"hardware_contact\", \"css\": null, \"name\": \"hardware_contact\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Hardware Contact\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"1e3156d8-da84-4242-9fc1-45c0e03dfd87\", \"attached_element_id\": \"9d0b4e6a-6a23-402f-8c10-0207e5d4b503\"}], \"position\": 2}, {\"id\": \"408f3279-9e95-4684-82f9-9f26bc1b7f47\", \"class\": \"col-lg-3\", \"controls\": [{\"id\": \"c3fcddc7-46a5-4709-a905-a45adf94f356\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"Software Contract:\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"408f3279-9e95-4684-82f9-9f26bc1b7f47\", \"attached_element_id\": \"9d0b4e6a-6a23-402f-8c10-0207e5d4b503\"}], \"position\": 3}, {\"id\": \"b7570c58-f90e-45af-9cd9-0f414d039ef2\", \"class\": \"col-lg-3 border-right\", \"controls\": [{\"id\": \"software_contact\", \"css\": null, \"name\": \"software_contact\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Software Contact\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"b7570c58-f90e-45af-9cd9-0f414d039ef2\", \"attached_element_id\": \"9d0b4e6a-6a23-402f-8c10-0207e5d4b503\"}], \"position\": 4}], \"class\": \"four-column field-dragger\", \"order\": 5, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"4\"}, {\"id\": \"5ccc0699-e850-4ad9-9119-60d416fcbb6b\", \"name\": \"Four Column\", \"child\": [{\"id\": \"1efddc40-3eb5-4574-adb2-4a5c08f88a75\", \"class\": \"col-lg-3\", \"controls\": [{\"id\": \"c3fcddc7-46a5-4709-a905-a45adf94f356\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"Hardware Tel:\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"1efddc40-3eb5-4574-adb2-4a5c08f88a75\", \"attached_element_id\": \"5ccc0699-e850-4ad9-9119-60d416fcbb6b\"}], \"position\": 1}, {\"id\": \"1e3156d8-da84-4242-9fc1-45c0e03dfd87\", \"class\": \"col-lg-3\", \"controls\": [{\"id\": \"hardware_phone\", \"css\": null, \"name\": \"hardware_phone\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Hardware Phone\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"1e3156d8-da84-4242-9fc1-45c0e03dfd87\", \"attached_element_id\": \"5ccc0699-e850-4ad9-9119-60d416fcbb6b\"}], \"position\": 2}, {\"id\": \"408f3279-9e95-4684-82f9-9f26bc1b7f47\", \"class\": \"col-lg-3\", \"controls\": [{\"id\": \"c3fcddc7-46a5-4709-a905-a45adf94f356\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"Software Tel:\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"408f3279-9e95-4684-82f9-9f26bc1b7f47\", \"attached_element_id\": \"5ccc0699-e850-4ad9-9119-60d416fcbb6b\"}], \"position\": 3}, {\"id\": \"b7570c58-f90e-45af-9cd9-0f414d039ef2\", \"class\": \"col-lg-3 border-right\", \"controls\": [{\"id\": \"software_phone\", \"css\": null, \"name\": \"software_phone\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Software Phone\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"b7570c58-f90e-45af-9cd9-0f414d039ef2\", \"attached_element_id\": \"5ccc0699-e850-4ad9-9119-60d416fcbb6b\"}], \"position\": 4}], \"class\": \"four-column field-dragger\", \"order\": 5, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"4\"}, {\"id\": \"3a220c8d-7893-4ced-a370-6fc5697bce57\", \"name\": \"Two Column\", \"child\": [{\"id\": \"fe2a334d-d9e7-4aec-81ea-255a44a9c585\", \"class\": \"col-lg-3\", \"controls\": [{\"id\": \"c3fcddc7-46a5-4709-a905-a45adf94f356\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"Coverage Period\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"fe2a334d-d9e7-4aec-81ea-255a44a9c585\", \"attached_element_id\": \"3a220c8d-7893-4ced-a370-6fc5697bce57\"}], \"position\": 1}, {\"id\": \"abe5eaf1-cbbc-4bf9-8cd6-ba1b46aa34a7\", \"class\": \"col-lg-9 border-right\", \"controls\": [{\"id\": \"coverage_period_from\", \"css\": null, \"name\": \"coverage_period_from\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Coverage Period From\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"abe5eaf1-cbbc-4bf9-8cd6-ba1b46aa34a7\", \"attached_element_id\": \"3a220c8d-7893-4ced-a370-6fc5697bce57\"}, {\"id\": \"c3fcddc7-46a5-4709-a905-a45adf94f356\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": null, \"label\": \"Label\", \"value\": \"to\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"abe5eaf1-cbbc-4bf9-8cd6-ba1b46aa34a7\", \"attached_element_id\": \"3a220c8d-7893-4ced-a370-6fc5697bce57\"}, {\"id\": \"coverage_period_to\", \"css\": null, \"name\": \"coverage_period_to\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Coverage Period To\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"abe5eaf1-cbbc-4bf9-8cd6-ba1b46aa34a7\", \"attached_element_id\": \"3a220c8d-7893-4ced-a370-6fc5697bce57\"}], \"position\": 2}], \"class\": \"two-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1+3\"}], \"first_page\": [{\"id\": \"c3446582-ac09-4468-aede-6ef5d3d8904c\", \"name\": \"Single Column\", \"child\": [{\"id\": \"234454d2-4ff3-423b-9953-2e810539036a\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"logo_set_x3\", \"css\": null, \"name\": \"logo_set_x3\", \"show\": false, \"type\": \"tag\", \"class\": \"text-right\", \"label\": \"Logo Set X3\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"234454d2-4ff3-423b-9953-2e810539036a\", \"attached_element_id\": \"c3446582-ac09-4468-aede-6ef5d3d8904c\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"a708b278-4d12-4d10-a9ed-123496e63d46\", \"name\": \"Single Column\", \"child\": [{\"id\": \"4c31e040-1dda-4408-b817-379acd9a927d\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"7b3635a1-aefa-4c1b-b333-ebbdc9d78d40\", \"css\": null, \"src\": null, \"name\": \"h-1\", \"show\": false, \"type\": \"h\", \"class\": \"blue bold\", \"label\": \"Heading\", \"value\": \"Hewlett Packard Enterprise Proactive Care Services Contract\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"4c31e040-1dda-4408-b817-379acd9a927d\", \"attached_element_id\": \"a708b278-4d12-4d10-a9ed-123496e63d46\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"92b4908b-f1f6-4cfb-bc9a-d3c7ab6e0e01\", \"name\": \"Single Column\", \"child\": [{\"id\": \"e1534774-699a-4a53-8d5f-0f5c87f3ed9b\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"a40e4674-9753-4b71-b597-0e99533e69a0\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"blue\", \"label\": \"Label\", \"value\": \"For:\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"e1534774-699a-4a53-8d5f-0f5c87f3ed9b\", \"attached_element_id\": \"92b4908b-f1f6-4cfb-bc9a-d3c7ab6e0e01\"}, {\"id\": \"customer_name\", \"css\": null, \"name\": \"customer_name\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Customer Name\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"e1534774-699a-4a53-8d5f-0f5c87f3ed9b\", \"attached_element_id\": \"92b4908b-f1f6-4cfb-bc9a-d3c7ab6e0e01\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"9de67406-73f0-4a1d-bcc7-07b5be0e25bd\", \"name\": \"Single Column\", \"child\": [{\"id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"6491c8f7-abfa-45d4-9919-ade6e30467ba\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"blue\", \"label\": \"Label\", \"value\": \"From:\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"9de67406-73f0-4a1d-bcc7-07b5be0e25bd\"}, {\"id\": \"e5dc1741-1626-4cc1-83f2-9f5240ce2051\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"blue\", \"label\": \"Label\", \"value\": \"Support Warehouse Ltd\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"9de67406-73f0-4a1d-bcc7-07b5be0e25bd\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"2822dea4-98d7-49a4-a2cc-3974105338e7\", \"name\": \"Single Column\", \"child\": [{\"id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"b9687f36-af4b-4d17-a1f4-f9994cc8828b\", \"css\": null, \"src\": null, \"name\": \"h-1\", \"show\": false, \"type\": \"hr\", \"class\": \"blue\", \"label\": \"Line\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"2822dea4-98d7-49a4-a2cc-3974105338e7\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"580a29f4-ac36-449b-8128-2682745c2de1\", \"name\": \"Single Column\", \"child\": [{\"id\": \"ff4d6dfb-aa04-4209-9a4d-7d680f1c2313\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"a98e3882-6313-480a-9cc8-b851a54bfd44\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"blue bold\", \"label\": \"Label\", \"value\": \"Service Highlights\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"ff4d6dfb-aa04-4209-9a4d-7d680f1c2313\", \"attached_element_id\": \"580a29f4-ac36-449b-8128-2682745c2de1\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"8250bb76-17c7-458d-9cb8-33b13c814ccd\", \"name\": \"Single Column\", \"child\": [{\"id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"d47630fa-d047-4a7b-8bfd-8db4bdc9d6ff\", \"css\": null, \"src\": null, \"name\": \"richtext-1\", \"show\": false, \"type\": \"richtext\", \"class\": null, \"label\": \"Rich Text\", \"value\": \"<ul><li>A set of proactive deliverables aimed at minimising the risk of unplanned downtime</li><li>Critical firmware updates can be accessed via the HPE Support Centre portal</li><li>Insight Remote Support providing automatic call logging and diagnosis (installation required)</li><li>All parts and labour</li><li>Telephone technical support including initial troubleshooting</li></ul>\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"8250bb76-17c7-458d-9cb8-33b13c814ccd\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"8b551358-0833-4b96-a305-ccb0df4f1220\", \"name\": \"Single Column\", \"child\": [{\"id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"34f281fb-553d-4e3a-9a15-88f3377bc5c9\", \"css\": null, \"src\": null, \"name\": \"h-1\", \"show\": false, \"type\": \"h\", \"class\": \"blue bold\", \"label\": \"Heading\", \"value\": \"Service Contract Summary\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"8b551358-0833-4b96-a305-ccb0df4f1220\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"66cbdba3-108b-4416-9c62-a8749c853d95\", \"name\": \"Single Column\", \"child\": [{\"id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"6491c8f7-abfa-45d4-9919-ade6e30467ba\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"Support start date:\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"66cbdba3-108b-4416-9c62-a8749c853d95\"}, {\"id\": \"support_start_assumed_char\", \"css\": null, \"name\": \"support_start_assumed_char\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Support Start Assumed (*)\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"66cbdba3-108b-4416-9c62-a8749c853d95\"}, {\"id\": \"support_start\", \"css\": null, \"name\": \"support_start\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Support Start Date\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"66cbdba3-108b-4416-9c62-a8749c853d95\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"9d5bad1a-8805-493d-89b6-a48823a32c89\", \"name\": \"Single Column\", \"child\": [{\"id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"6491c8f7-abfa-45d4-9919-ade6e30467ba\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"Support end date:\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"9d5bad1a-8805-493d-89b6-a48823a32c89\"}, {\"id\": \"support_end_assumed_char\", \"css\": null, \"name\": \"support_end_assumed_char\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Support End Assumed (*)\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"9d5bad1a-8805-493d-89b6-a48823a32c89\"}, {\"id\": \"support_end\", \"css\": null, \"name\": \"support_end\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Support End Date\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"9d5bad1a-8805-493d-89b6-a48823a32c89\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"c3346352-a0fa-46dc-88af-89d79cc9ab7a\", \"name\": \"Single Column\", \"child\": [{\"id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"6491c8f7-abfa-45d4-9919-ade6e30467ba\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"Service Agreement ID:\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"c3346352-a0fa-46dc-88af-89d79cc9ab7a\"}, {\"id\": \"service_agreement_id\", \"css\": null, \"name\": \"service_agreement_id\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Service Agreement Id\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"c3346352-a0fa-46dc-88af-89d79cc9ab7a\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"f7610fa9-f2fc-449f-96fb-cdde11b84630\", \"name\": \"Single Column\", \"child\": [{\"id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"6491c8f7-abfa-45d4-9919-ade6e30467ba\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"System Handle (Support Account Reference/SAR):\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"f7610fa9-f2fc-449f-96fb-cdde11b84630\"}, {\"id\": \"system_handle\", \"css\": null, \"name\": \"system_handle\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"System Handle\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"f7610fa9-f2fc-449f-96fb-cdde11b84630\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"91310edf-b865-45e9-87bd-8107a5249dc7\", \"name\": \"Single Column\", \"child\": [{\"id\": \"00cc9bcb-469d-446e-86a1-486e4c1411ba\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"9ca2a088-967d-4726-bc79-f1ed07b713c2\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"PO Number\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"00cc9bcb-469d-446e-86a1-486e4c1411ba\", \"attached_element_id\": \"91310edf-b865-45e9-87bd-8107a5249dc7\"}, {\"id\": \"purchase_order_number\", \"css\": null, \"name\": \"purchase_order_number\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Purchase Order Number\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"00cc9bcb-469d-446e-86a1-486e4c1411ba\", \"attached_element_id\": \"91310edf-b865-45e9-87bd-8107a5249dc7\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"b32932c7-69af-4af9-be45-7aa6fe9fcb5e\", \"name\": \"Single Column\", \"child\": [{\"id\": \"00cc9bcb-469d-446e-86a1-486e4c1411ba\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"9ca2a088-967d-4726-bc79-f1ed07b713c2\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"VAT Number\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"00cc9bcb-469d-446e-86a1-486e4c1411ba\", \"attached_element_id\": \"b32932c7-69af-4af9-be45-7aa6fe9fcb5e\"}, {\"id\": \"vat_number\", \"css\": null, \"name\": \"vat_number\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"VAT Number\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"00cc9bcb-469d-446e-86a1-486e4c1411ba\", \"attached_element_id\": \"b32932c7-69af-4af9-be45-7aa6fe9fcb5e\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"40f2333a-8cee-4bac-b731-05f13f25e66e\", \"name\": \"Single Column\", \"child\": [{\"id\": \"d43a6092-822b-471d-b271-89e8e0db0944\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"ec28408a-edc3-4cd0-b50f-e712de479896\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"Visit:\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"d43a6092-822b-471d-b271-89e8e0db0944\", \"attached_element_id\": \"40f2333a-8cee-4bac-b731-05f13f25e66e\"}, {\"id\": \"ec28408a-edc3-4cd0-b50f-e712de479896\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"blue\", \"label\": \"Label\", \"value\": \"www.supportwarehouse.com\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"d43a6092-822b-471d-b271-89e8e0db0944\", \"attached_element_id\": \"40f2333a-8cee-4bac-b731-05f13f25e66e\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"b8481581-d6a2-4e4a-bc8d-030a5399a751\", \"name\": \"Single Column\", \"child\": [{\"id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"34f281fb-553d-4e3a-9a15-88f3377bc5c9\", \"css\": null, \"src\": null, \"name\": \"h-1\", \"show\": false, \"type\": \"h\", \"class\": \"bold blue\", \"label\": \"Heading\", \"value\": \"Contact us\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"b8481581-d6a2-4e4a-bc8d-030a5399a751\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"a1e132c4-de44-4469-9f76-6526cd2c4c91\", \"name\": \"Single Column\", \"child\": [{\"id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"6491c8f7-abfa-45d4-9919-ade6e30467ba\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"Tel:\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"a1e132c4-de44-4469-9f76-6526cd2c4c91\"}, {\"id\": \"6491c8f7-abfa-45d4-9919-ade6e30467ba\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"s4-form-control\", \"label\": \"Label\", \"value\": \"0800 022 79 62\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"a1e132c4-de44-4469-9f76-6526cd2c4c91\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"1773b9b0-ffe0-4a87-b5af-3190ac293214\", \"name\": \"Single Column\", \"child\": [{\"id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"6491c8f7-abfa-45d4-9919-ade6e30467ba\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"bold\", \"label\": \"Label\", \"value\": \"Email:\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"1773b9b0-ffe0-4a87-b5af-3190ac293214\"}, {\"id\": \"6491c8f7-abfa-45d4-9919-ade6e30467ba\", \"css\": null, \"src\": null, \"name\": \"l-1\", \"show\": false, \"type\": \"label\", \"class\": \"blue\", \"label\": \"Label\", \"value\": \"nl@supportwarehouse.com\", \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": false, \"is_required\": false, \"attached_child_id\": \"f4694516-8961-4f9e-81c9-f824ef946b49\", \"attached_element_id\": \"1773b9b0-ffe0-4a87-b5af-3190ac293214\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}, {\"id\": \"b4353309-f8b2-4d2e-8363-bddcc9d40a93\", \"name\": \"Single Column\", \"child\": [{\"id\": \"d9c04151-7dac-46e7-b33d-fd9afa66faa0\", \"class\": \"col-lg-12 border-right\", \"controls\": [{\"id\": \"footer_notes\", \"css\": null, \"name\": \"footer_notes\", \"show\": false, \"type\": \"tag\", \"class\": \"s4-form-control\", \"label\": \"Footer notes\", \"value\": null, \"is_field\": true, \"is_image\": false, \"droppable\": false, \"is_system\": true, \"is_required\": false, \"attached_child_id\": \"d9c04151-7dac-46e7-b33d-fd9afa66faa0\", \"attached_element_id\": \"b4353309-f8b2-4d2e-8363-bddcc9d40a93\"}], \"position\": 1}], \"class\": \"single-column field-dragger\", \"order\": 1, \"controls\": [], \"is_field\": false, \"droppable\": false, \"decoration\": \"1\"}], \"payment_page\": []}", true),
//            'data_headers' => json_decode("{\"qty\": {\"key\": \"qty\", \"label\": \"Quantity\", \"value\": \"Quantity\"}, \"date_to\": {\"key\": \"date_to\", \"label\": \"To Date\", \"value\": \"To Date\"}, \"date_from\": {\"key\": \"date_from\", \"label\": \"From Date\", \"value\": \"From Date1\"}, \"serial_no\": {\"key\": \"serial_no\", \"label\": \"Serial Number\", \"value\": \"Serial Number\"}, \"product_no\": {\"key\": \"product_no\", \"label\": \"Product No\", \"value\": \"Product No\"}, \"description\": {\"key\": \"description\", \"label\": \"Description\", \"value\": \"Description\"}}", true)
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'business_division_id',
                'contract_type_id',
                'company_id',
                'vendor_id',
                'countries',
                'form_data',
                'data_headers',
                'data_headers_keyed',
            ]);
    }

    /**
     * Test an ability to copy an existing sales order template.
     *
     * @return void
     */
    public function testCanCopySalesOrderTemplate()
    {
        $template = factory(SalesOrderTemplate::class)->create();

        $this->authenticateApi();

        $response = $this->putJson('api/sales-order-templates/'.$template->getKey().'/copy')
            ->assertCreated()
            ->assertJsonStructure([
                'id',
                'name',
                'business_division_id',
                'contract_type_id',
                'company_id',
                'vendor_id',
                'countries',
                'form_data',
                'data_headers',
                'data_headers_keyed',
            ]);

        $response = $this->getJson('api/sales-order-templates/'.$response->json('id'))
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'business_division_id',
                'contract_type_id',
                'company_id',
                'vendor_id',
                'countries',
                'form_data',
                'data_headers',
                'data_headers_keyed',
            ]);

        $this->assertStringContainsString('[copy]', $response->json('name'));
    }

    /**
     * Test an ability to delete an existing sales order template.
     *
     * @return void
     */
    public function testCanDeleteSalesOrderTemplate()
    {
        $template = factory(SalesOrderTemplate::class)->create();

        $this->authenticateApi();

        $this->deleteJson('api/sales-order-templates/'.$template->getKey())
            ->assertNoContent();

        $this->getJson('api/sales-order-templates/'.$template->getKey())
            ->assertNotFound();
    }

    /**
     * Test an ability to update an existing sales order template when the actor is the team leader of the template owner.
     */
    public function testCanUpdateHpeContractTemplateOwnedByLedTeamUser(): void
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions(['view_sales_order_templates', 'create_sales_order_templates', 'update_own_sales_order_templates']);

        /** @var \App\Domain\Team\Models\Team $team */
        $team = factory(Team::class)->create();

        /** @var User $teamLeader */
        $teamLeader = User::factory()->create(['team_id' => $team->getKey()]);
        $teamLeader->syncRoles($role);

        /** @var \App\Domain\User\Models\User $ledUser */
        $ledUser = User::factory()->create(['team_id' => $team->getKey()]);
        $ledUser->syncRoles($role);

        /** @var \App\Domain\Worldwide\Models\SalesOrderTemplate $template */
        $template = factory(SalesOrderTemplate::class)->create(['user_id' => $ledUser->getKey()]);

        $data = [
            'business_division_id' => $template->businessDivision()->getParentKey(),
            'contract_type_id' => $template->contractType()->getParentKey(),
            'company_id' => $template->company()->getParentKey(),
            'vendor_id' => $template->vendor()->getParentKey(),
            'countries' => Country::query()->limit(2)->get()->modelKeys(),
            'name' => Str::random(40),
        ];

        $this->authenticateApi($teamLeader);

        $this->patchJson('api/sales-order-templates/'.$template->getKey(), $data)
//            ->dump()
            ->assertForbidden();

        $team->teamLeaders()->sync($teamLeader);

        $this->app->forgetInstance(UserTeamGate::class);

        $this->authenticateApi($teamLeader);

        $this->patchJson('api/sales-order-templates/'.$template->getKey(), $data)
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/sales-order-templates/'.$template->getKey())
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
            ]);

        $this->assertSame($data['name'], $response->json('name'));
    }

    /**
     * Test an ability to delete an existing sales order template owned when the actor is the team leader of the template owner.
     */
    public function testCanDeleteHpeContractTemplateOwnedByLedTeamUser(): void
    {
        /** @var Role $role */
        $role = factory(Role::class)->create();

        $role->syncPermissions(['view_sales_order_templates', 'create_sales_order_templates', 'update_own_sales_order_templates', 'delete_own_sales_order_templates']);

        /** @var Team $team */
        $team = factory(Team::class)->create();

        /** @var \App\Domain\User\Models\User $teamLeader */
        $teamLeader = User::factory()->create(['team_id' => $team->getKey()]);
        $teamLeader->syncRoles($role);

        /** @var \App\Domain\User\Models\User $ledUser */
        $ledUser = User::factory()->create(['team_id' => $team->getKey()]);
        $ledUser->syncRoles($role);

        /** @var \App\Domain\Worldwide\Models\SalesOrderTemplate $template */
        $template = factory(SalesOrderTemplate::class)->create(['user_id' => $ledUser->getKey()]);

        $this->authenticateApi($teamLeader);

        $this->deleteJson('api/sales-order-templates/'.$template->getKey())
//            ->dump()
            ->assertForbidden();

        $team->teamLeaders()->sync($teamLeader);

        $this->app->forgetInstance(UserTeamGate::class);

        $this->authenticateApi($teamLeader);

        $this->deleteJson('api/sales-order-templates/'.$template->getKey())
//            ->dump()
            ->assertNoContent();

        $this->getJson('api/sales-order-templates/'.$template->getKey())
            ->assertNotFound();
    }
}
