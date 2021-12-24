<?php

namespace Tests\Feature;

use App\Models\System\CustomFieldValue;
use Database\Seeders\CustomFieldSeeder;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomFieldTest extends TestCase
{
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
                    'id', 'field_name'
                ]
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
                    'id', 'field_value', 'entity_order', 'is_default'
                ]
            ]);

        $this->assertNotEmpty($response->json());

        $response = $this->getJson('api/custom-field-values/quote_dead_reasons')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default'
                ]
            ]);

        $this->assertNotEmpty($response->json());

        $response = $this->getJson('api/custom-field-values/quote_payment_terms')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default'
                ]
            ]);

        $this->assertNotEmpty($response->json());

        $response = $this->getJson('api/custom-field-values/task_statuses')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order', 'is_default'
                ]
            ]);

        $this->assertNotEmpty($response->json());

        $response = $this->getJson('api/custom-field-values/'.Str::random(40));

        $this->assertEmpty($response->json());
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
                    'id', 'field_value', 'entity_order'
                ]
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
            'field_values' => $fieldValues
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order'
                ]
            ]);

        $response = $this->getJson('api/custom-field-values/opportunity_lost_reasons')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order'
                ]
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
                    'id', 'field_value', 'entity_order'
                ]
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
            'field_values' => $fieldValues
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order'
                ]
            ]);

        $response = $this->getJson('api/custom-field-values/quote_payment_terms')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order'
                ]
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
                    'id', 'field_value', 'entity_order'
                ]
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
            'field_values' => $fieldValues
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order'
                ]
            ]);

        $response = $this->getJson('api/custom-field-values/quote_dead_reasons')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order'
                ]
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
                    'id', 'field_value', 'entity_order'
                ]
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
            'field_values' => $fieldValues
        ])
//            ->dump()
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order'
                ]
            ]);

        $response = $this->getJson('api/custom-field-values/task_statuses')
            ->assertOk()
            ->assertJsonStructure([
                '*' => [
                    'id', 'field_value', 'entity_order'
                ]
            ]);

        $this->assertCount(count($fieldValues), $response->json());

        foreach ($fieldValues as $key => $value) {
            $this->assertEquals($response->json($key.'.field_value'), $value['field_value']);
        }
    }
}
