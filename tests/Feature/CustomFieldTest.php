<?php

namespace Tests\Feature;

use App\Models\System\CustomFieldValue;
use Database\Seeders\CustomFieldSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomFieldTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test an ability to view list of existing custom fields.
     *
     * @return void
     */
    public function testCanViewListOfCustomFields()
    {
        $this->authenticateApi();

        $this->authenticateApi();

        $this->app['db.connection']->table('custom_fields')->delete();

        $this->seed(CustomFieldSeeder::class);

        $response = $this->getJson('api/custom-fields')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_name',
                ],
            ]);

        $this->assertNotEmpty($response->json());
    }

    /**
     * Test an ability to view values of custom field by field name.
     *
     * @return void
     */
    public function testCanViewValuesOfCustomFieldByFieldName()
    {
        $this->authenticateApi();

        $this->app['db.connection']->table('custom_fields')->delete();

        $this->seed(CustomFieldSeeder::class);

        $response = $this->getJson('api/custom-field-values/opportunity_lost_reasons')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $this->assertNotEmpty($response->json());

        $response = $this->getJson('api/custom-field-values/quote_dead_reasons')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $this->assertNotEmpty($response->json());

        $response = $this->getJson('api/custom-field-values/quote_payment_terms')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $this->assertNotEmpty($response->json());

        $response = $this->getJson('api/custom-field-values/task_statuses')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $this->assertNotEmpty($response->json());

        $response = $this->getJson('api/custom-field-values/'.Str::random(40));

        $response->assertNotFound();
    }

    /**
     * Test an ability to update values of custom fields.
     *
     * @return void
     */
    public function testCanUpdateValuesOfOpportunityLostReasonsCustomField()
    {
        $this->authenticateApi();

        $this->app['db.connection']->table('custom_fields')->delete();

        $this->seed(CustomFieldSeeder::class);

        $response = $this->getJson('api/custom-field-values/opportunity_lost_reasons')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $existingUpdatedOpportunityLostReasons = array_slice($response->json(), 0, 3);
        $existingNonTouchedOpportunityLostReasons = array_slice($response->json(), 3, 3);

        $existingUpdatedOpportunityLostReasons = array_map(function (array $fieldValue) {
            return ['field_value' => Str::random(40)] + $fieldValue;
        }, $existingUpdatedOpportunityLostReasons);

        $newOpportunityLostReasons = factory(CustomFieldValue::class, 10)->raw();

        $fieldValues = array_merge($existingNonTouchedOpportunityLostReasons, $existingUpdatedOpportunityLostReasons, $newOpportunityLostReasons);

        $fieldValues = array_map(function (array $fieldValueData) {
            return array_merge($fieldValueData, ['is_default' => false]);
        }, $fieldValues);

        $this->putJson('api/custom-field-values/opportunity_lost_reasons', [
            'field_values' => $fieldValues,
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $response = $this->getJson('api/custom-field-values/opportunity_lost_reasons')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $this->assertCount(count($fieldValues), $response->json());

        foreach ($fieldValues as $key => $value) {
            $this->assertEquals($response->json($key.'.field_value'), $value['field_value']);
        }
    }

    /**
     * Test an ability to update values of custom fields.
     *
     * @return void
     */
    public function testCanUpdateValuesOfQuotePaymentTermsCustomField()
    {
        $this->authenticateApi();

        $this->app['db.connection']->table('custom_fields')->delete();

        $this->seed(CustomFieldSeeder::class);

        $response = $this->getJson('api/custom-field-values/quote_payment_terms')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $existingUpdatedOpportunityLostReasons = array_slice($response->json(), 0, 3);
        $existingNonTouchedOpportunityLostReasons = array_slice($response->json(), 3, 3);

        $existingUpdatedOpportunityLostReasons = array_map(function (array $fieldValue) {
            return ['field_value' => Str::random(40)] + $fieldValue;
        }, $existingUpdatedOpportunityLostReasons);

        $newOpportunityLostReasons = factory(CustomFieldValue::class, 10)->raw();

        $fieldValues = array_merge($existingNonTouchedOpportunityLostReasons, $existingUpdatedOpportunityLostReasons, $newOpportunityLostReasons);

        $fieldValues = array_map(function (array $fieldValueData) {
            return array_merge($fieldValueData, ['is_default' => false]);
        }, $fieldValues);

        $this->putJson('api/custom-field-values/quote_payment_terms', [
            'field_values' => $fieldValues,
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $response = $this->getJson('api/custom-field-values/quote_payment_terms')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $this->assertCount(count($fieldValues), $response->json());

        foreach ($fieldValues as $key => $value) {
            $this->assertEquals($response->json($key.'.field_value'), $value['field_value']);
        }
    }

    /**
     * Test an ability to update values of custom fields.
     *
     * @return void
     */
    public function testCanUpdateValuesOfQuoteDeadReasonsCustomField()
    {
        $this->authenticateApi();

        $this->app['db.connection']->table('custom_fields')->delete();

        $this->seed(CustomFieldSeeder::class);

        $response = $this->getJson('api/custom-field-values/quote_dead_reasons')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $existingUpdatedOpportunityLostReasons = array_slice($response->json(), 0, 3);
        $existingNonTouchedOpportunityLostReasons = array_slice($response->json(), 3, 3);

        $existingUpdatedOpportunityLostReasons = array_map(function (array $fieldValue) {
            return ['field_value' => Str::random(40)] + $fieldValue;
        }, $existingUpdatedOpportunityLostReasons);

        $newOpportunityLostReasons = factory(CustomFieldValue::class, 10)->raw();

        $fieldValues = array_merge($existingNonTouchedOpportunityLostReasons, $existingUpdatedOpportunityLostReasons, $newOpportunityLostReasons);

        $fieldValues = array_map(function (array $fieldValueData) {
            return array_merge($fieldValueData, ['is_default' => false]);
        }, $fieldValues);

        $this->putJson('api/custom-field-values/quote_dead_reasons', [
            'field_values' => $fieldValues,
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $response = $this->getJson('api/custom-field-values/quote_dead_reasons')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $this->assertCount(count($fieldValues), $response->json());

        foreach ($fieldValues as $key => $value) {
            $this->assertEquals($response->json($key.'.field_value'), $value['field_value']);
        }
    }

    /**
     * Test an ability to update values of custom fields.
     *
     * @return void
     */
    public function testCanUpdateValuesOfTaskStatusesCustomField()
    {
        $this->authenticateApi();

        $this->app['db.connection']->table('custom_fields')->delete();

        $this->seed(CustomFieldSeeder::class);

        $response = $this->getJson('api/custom-field-values/task_statuses')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $existingUpdatedOpportunityLostReasons = array_slice($response->json(), 0, 3);
        $existingNonTouchedOpportunityLostReasons = array_slice($response->json(), 3, 3);

        $existingUpdatedOpportunityLostReasons = array_map(function (array $fieldValue) {
            return ['field_value' => Str::random(40)] + $fieldValue;
        }, $existingUpdatedOpportunityLostReasons);

        $newOpportunityLostReasons = factory(CustomFieldValue::class, 10)->raw();

        $fieldValues = array_merge($existingNonTouchedOpportunityLostReasons, $existingUpdatedOpportunityLostReasons, $newOpportunityLostReasons);

        $fieldValues = array_map(function (array $fieldValueData) {
            return array_merge($fieldValueData, ['is_default' => false]);
        }, $fieldValues);

        $this->putJson('api/custom-field-values/task_statuses', [
            'field_values' => $fieldValues,
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $response = $this->getJson('api/custom-field-values/task_statuses')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default', 'allowed_by',
                ],
            ]);

        $this->assertCount(count($fieldValues), $response->json());

        foreach ($fieldValues as $key => $value) {
            $this->assertEquals($response->json($key.'.field_value'), $value['field_value']);
        }
    }

    /**
     * Test an ability to update values of custom fields.
     *
     * @return void
     */
    public function testCanUpdateValuesOfOpportunityDistributor1CustomField(): void
    {
        $this->authenticateApi();

        $this->app['db.connection']->table('custom_fields')->delete();

        $this->seed(CustomFieldSeeder::class);

        $response = $this->getJson('api/v2/custom-field-values/opportunity_distributor1')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'parent_field_id',
                    'parent_field_name',
                    'field_name',
                    'field_values' => [
                        '*' => [
                            'id',
                            'field_value',
                            'allowed_by',
                            'entity_order',
                            'is_default',
                        ],
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.parent_field_id'));
        $this->assertNotEmpty($response->json('data.parent_field_name'));

        $responseOfParentFieldValues = $this->getJson('api/v2/custom-field-values/'.$response->json('data.parent_field_name'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'parent_field_id',
                    'parent_field_name',
                    'field_name',
                    'field_values' => [
                        '*' => [
                            'id',
                            'field_value',
                            'allowed_by',
                            'entity_order',
                            'is_default',
                        ],
                    ],
                ],
            ]);

        $parentFieldValuesIds = $responseOfParentFieldValues->json('data.field_values.*.id');


        $data = collect()->times(10, static function (int $i) use ($parentFieldValuesIds) {
            return [
                'id' => null,
                'field_value' => Str::random(10),
                'allowed_by' => Arr::random($parentFieldValuesIds, mt_rand(2, 10)),
                'is_default' => false,
                'entity_order' => $i,
            ];
        })
            ->all();

        $this->putJson('api/v2/custom-field-values/opportunity_distributor1', ['field_values' => $data])
//            ->dump()
            ->assertOk();
    }

    public function testCanViewExtendedCustomFieldValue(): void
    {
        $this->authenticateApi();

        $this->app['db.connection']->table('custom_fields')->delete();

        $this->seed(CustomFieldSeeder::class);

        $response = $this->getJson('api/v2/custom-field-values/opportunity_distributor1')
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'parent_field_id',
                    'parent_field_name',
                    'field_name',
                    'field_values' => [
                        '*' => [
                            'id',
                            'field_value',
                            'allowed_by',
                            'entity_order',
                            'is_default',
                        ],
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.parent_field_id'));
        $this->assertNotEmpty($response->json('data.parent_field_name'));

        $responseOfParentFieldValues = $this->getJson('api/v2/custom-field-values/'.$response->json('data.parent_field_name'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'parent_field_id',
                    'parent_field_name',
                    'field_name',
                    'field_values' => [
                        '*' => [
                            'id',
                            'field_value',
                            'allowed_by',
                            'entity_order',
                            'is_default',
                        ],
                    ],
                ],
            ]);

        $parentFieldValuesIds = $responseOfParentFieldValues->json('data.field_values.*.id');


        $data = collect()->times(2, static function (int $i) use ($parentFieldValuesIds) {
            return [
                'id' => null,
                'field_value' => Str::random(10),
                'allowed_by' => Arr::random($parentFieldValuesIds, mt_rand(2, 10)),
                'is_default' => false,
                'entity_order' => $i,
            ];
        })
            ->all();

        $this->putJson('api/v2/custom-field-values/opportunity_distributor1', ['field_values' => $response->json('data.field_values')])
//            ->dump()
            ->assertOk();

        $response = $this->getJson('api/v2/custom-field-values/opportunity_distributor1')
//            ->dump()
            ->assertOk();

        $parentFieldName = $response->json('data.parent_field_name');
        $allowedById = $response->json('data.field_values.0.allowed_by.0');

        $this->assertNotEmpty($allowedById);

        $this->getJson("api/v2/custom-field-values/$parentFieldName/$allowedById")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'field_value',
                    'allowed_by_values' => [
                        '*' => [
                            'id',
                            'custom_field_id',
                            'field_value',
                            'is_default',
                            'entity_order',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'allowed_for_values' => [
                        '*' => [
                            'id',
                            'custom_field_id',
                            'field_value',
                            'is_default',
                            'entity_order',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'entity_order',
                    'is_default',
                ],
            ]);
    }

    /**
     * Test an ability to calculate value of a custom field.
     */
    public function testCanCalculateCustomFieldValue(): void
    {
        $this->authenticateApi();

        $this->postJson('api/v2/custom-fields/opportunity_ranking/calculate', [
            'variables' => [
                'customer_order_date' => null,
                'personal_rating' => '2',
            ]
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                'result',
                'errors',
            ]);
    }
}
