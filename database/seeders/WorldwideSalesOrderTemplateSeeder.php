<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WorldwideSalesOrderTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Throwable
     */
    public function run()
    {
        $wwContractTemplates = json_decode(file_get_contents(database_path('seeders/models/ww_master_contract_sales_order_templates.json')), true);

        $connection = $this->container['db.connection'];

        $connection->transaction(function () use ($wwContractTemplates, $connection) {

            foreach ($wwContractTemplates as $template) {

                $connection->table('template_schemas')
                    ->upsert([
                        'id' => $template['template_schema']['id'],
                        'form_data' => $template['template_schema']['form_data'],
                        'data_headers' => $template['template_schema']['data_headers'],
                    ], null, [
                        'form_data' => $template['template_schema']['form_data'],
                        'data_headers' => $template['template_schema']['data_headers'],
                    ]);

                $connection->table('sales_order_templates')
                    ->upsert([
                        'id' => $template['id'],
                        'business_division_id' => $template['business_division_id'],
                        'contract_type_id' => $template['contract_type_id'],
                        'company_id' => $template['company_id'],
                        'vendor_id' => $template['vendor_id'],
                        'currency_id' => $template['currency_id'],
                        'name' => $template['name'],
                        'template_schema_id' => $template['template_schema']['id'],
                        'is_system' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'activated_at' => now()
                    ], null, [
                        'company_id' => $template['company_id'],
                        'vendor_id' => $template['vendor_id'],
                        'currency_id' => $template['currency_id'],
                        'name' => $template['name'],
                        'template_schema_id' => $template['template_schema']['id'],
                        'is_system' => true
                    ]);

                foreach ($template['country_model_keys'] as $countryKey) {

                    $connection->table('country_sales_order_template')
                        ->insertOrIgnore([
                            'sales_order_template_id' => $template['id'],
                            'country_id' => $countryKey
                        ]);

                }

            }

        });
    }
}
