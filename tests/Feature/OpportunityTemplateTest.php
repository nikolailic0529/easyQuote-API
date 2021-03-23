<?php

namespace Tests\Feature;

use App\Services\Opportunity\OpportunityTemplateService;
use Illuminate\Support\Str;
use Tests\TestCase;

class OpportunityTemplateTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $valuestorePath = rtrim($this->app->storagePath(), '/').'/framework/testing/valuestore/';

        if (!is_dir($valuestorePath)) {
            mkdir($valuestorePath, 0777, true);
        }

        $this->app->instance(OpportunityTemplateService::class, new OpportunityTemplateService(
            $valuestorePath.Str::random(40).'.json'
        ));
    }

    /**
     * Test an ability to view opportunity template schema.
     *
     * @return void
     */
    public function testCanViewOpportunityTemplate()
    {
        $this->authenticateApi();

        $this->get('api/opportunity-template')
            ->assertOk();
    }

    /**
     * Test an ability to update opportunity template schema.
     *
     * @return void
     */
    public function testCanUpdateOpportunityTemplate()
    {
        $this->authenticateApi();

        $randomUuid = (string)Str::uuid();

        $schema = <<<SCHEMA
[
    {
        "id": "$randomUuid",
        "name": "Single Column",
        "child": [
            {
                "id": "ed16b47c-8ee5-43fb-bb9a-6795b9879e5f",
                "class": "col-lg-12",
                "controls": [
                    {
                        "type": "label",
                        "name": "l-1",
                        "class": null,
                        "droppable": false,
                        "is_field": true,
                        "attached_element_id": "0044af50-4bab-4c19-bfc3-269fded3b70d",
                        "attached_child_id": "ed16b47c-8ee5-43fb-bb9a-6795b9879e5f",
                        "id": "24714d89-dcb4-4600-88b6-f3b4699972b7",
                        "is_system": false,
                        "label": "Label",
                        "is_required": false,
                        "value": "Detail",
                        "is_image": false,
                        "src": null,
                        "css": null,
                        "show": false
                    },
                    {
                        "id": "cd2a2155-5cc8-4c37-8a1f-403697bd56c5",
                        "css": null,
                        "src": null,
                        "name": "richtext-1",
                        "show": false,
                        "type": "richtext",
                        "class": null,
                        "label": "Rich Text",
                        "value": null,
                        "is_field": true,
                        "is_image": false,
                        "droppable": false,
                        "is_system": false,
                        "is_required": false,
                        "attached_child_id": "ed16b47c-8ee5-43fb-bb9a-6795b9879e5f",
                        "attached_element_id": "0044af50-4bab-4c19-bfc3-269fded3b70d",
                        "field_required": true
                    }
                ],
                "position": 1
            }
        ],
        "class": "single-column field-dragger",
        "order": 1,
        "controls": [],
        "is_field": false,
        "droppable": false,
        "decoration": "1"
    },
    {
        "id": "53c54c8a-1858-470a-ac57-7915e230ce83",
        "droppable": false,
        "name": "Two Column",
        "class": "two-column field-dragger",
        "decoration": "2",
        "order": 2,
        "is_field": false,
        "child": [
            {
                "class": "col-lg-6",
                "position": 1,
                "id": "82822f81-9582-4d3d-a2f9-c7bfff3835e4",
                "controls": [
                    {
                        "type": "label",
                        "name": "l-1",
                        "class": null,
                        "droppable": false,
                        "is_field": true,
                        "attached_element_id": "53c54c8a-1858-470a-ac57-7915e230ce83",
                        "attached_child_id": "82822f81-9582-4d3d-a2f9-c7bfff3835e4",
                        "id": "ae8d18d6-b797-479a-b24f-4b1321ba10c1",
                        "is_system": false,
                        "label": "Label",
                        "is_required": false,
                        "value": "Status",
                        "is_image": false,
                        "src": null,
                        "css": null,
                        "show": false
                    },
                    {
                        "type": "dropdown",
                        "placeholder": null,
                        "name": "dropdown-1",
                        "class": "form-control",
                        "droppable": false,
                        "is_field": true,
                        "attached_element_id": "53c54c8a-1858-470a-ac57-7915e230ce83",
                        "attached_child_id": "82822f81-9582-4d3d-a2f9-c7bfff3835e4",
                        "id": "2a9a0728-0719-4fbf-b724-eb601f43e59a",
                        "is_system": false,
                        "label": "Dropdown",
                        "is_required": false,
                        "value": null,
                        "is_image": false,
                        "src": null,
                        "css": null,
                        "show": false,
                        "possibel_values": null,
                        "field_required": true,
                        "possible_values": "NS,I\/P,Wait,Compl,Def"
                    }
                ]
            },
            {
                "class": "col-lg-6",
                "id": "36e2e1ec-38cb-4d31-9b92-4808b1cca81a",
                "position": 2,
                "controls": [
                    {
                        "type": "label",
                        "name": "l-1",
                        "class": null,
                        "droppable": false,
                        "is_field": true,
                        "attached_element_id": "53c54c8a-1858-470a-ac57-7915e230ce83",
                        "attached_child_id": "36e2e1ec-38cb-4d31-9b92-4808b1cca81a",
                        "id": "ae8d18d6-b797-479a-b24f-4b1321ba10c1",
                        "is_system": false,
                        "label": "Label",
                        "is_required": false,
                        "value": "Sale Unit",
                        "is_image": false,
                        "src": null,
                        "css": null,
                        "show": false
                    },
                    {
                        "type": "dropdown",
                        "placeholder": null,
                        "name": "dropdown-1",
                        "class": "form-control",
                        "droppable": false,
                        "is_field": true,
                        "attached_element_id": "53c54c8a-1858-470a-ac57-7915e230ce83",
                        "attached_child_id": "36e2e1ec-38cb-4d31-9b92-4808b1cca81a",
                        "id": "ddedf672-f7d3-4fac-89cf-2fc712bc7f94",
                        "is_system": false,
                        "label": "Dropdown",
                        "is_required": false,
                        "value": null,
                        "is_image": false,
                        "src": null,
                        "css": null,
                        "show": false,
                        "possibel_values": null,
                        "field_required": true,
                        "possible_values": "Tasedi Deutchland,SWH"
                    }
                ]
            }
        ],
        "controls": []
    }
]
SCHEMA;

        $this->put('api/opportunity-template', ['form_data' => json_decode($schema, true)], ['accept' => 'application/json'])
            ->assertNoContent();

        $response = $this->getJson('api/opportunity-template')
            ->assertOk();

        $this->assertEquals(json_decode($schema, true), $response->json());
    }
}
