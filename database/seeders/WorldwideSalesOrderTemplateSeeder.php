<?php

namespace Database\Seeders;

use Illuminate\Database\ConnectionInterface;
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

        /** @var ConnectionInterface $connection */
        $connection = $this->container['db.connection'];

        $wwContractTemplates = collect($wwContractTemplates)
            ->map(function (array $template) use ($connection) {

                $schemaPath = database_path('seeders/models/template_schemas/'.$template['template_schema']);

                $template['template_schema'] = json_decode(file_get_contents($schemaPath), true);
                $template['template_schema']['form_data'] = json_encode($template['template_schema']['form_data']);
                $template['template_schema']['data_headers'] = json_encode($template['template_schema']['data_headers']);

                $templateSchemaID = $template['template_schema']['id'];

                $exists = $connection->table('template_schemas')
                    ->where('id', $templateSchemaID)
                    ->exists();

                $template['template_schema']['exists'] = $exists;

                return $template;
            })
            ->all();

        foreach ($wwContractTemplates as $template) {

            $connection->transaction(function () use ($template, $wwContractTemplates, $connection) {

                $connection->table('template_schemas')
                    ->where('id', $template['template_schema']['id'])
                    ->upsert([
                        'id' => $template['template_schema']['id'],
                        'form_data' => $template['template_schema']['form_data'],
                        'data_headers' => $template['template_schema']['data_headers'],
                    ], 'id', [
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
                        'activated_at' => now(),
                    ], 'id', [
                        'company_id' => $template['company_id'],
                        'vendor_id' => $template['vendor_id'],
                        'currency_id' => $template['currency_id'],
                        'name' => $template['name'],
                        'template_schema_id' => $template['template_schema']['id'],
                        'is_system' => true,
                    ]);

                foreach ($template['countries'] as $countryKey) {

                    $connection->table('country_sales_order_template')
                        ->insertOrIgnore([
                            'sales_order_template_id' => $template['id'],
                            'country_id' => $countryKey,
                        ]);

                }

            });

        }
    }
}
