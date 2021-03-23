<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WorldwideQuoteTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     * @throws \Throwable
     */
    public function run()
    {
        $wwContractQuoteTemplateUuid = '4f8bc11b-6109-41db-87f9-962c37dd8c7f';
        $wwContractQuoteTemplateSchema = file_get_contents(database_path('seeders/models/ww_contract_epd_master_quote_template_schema.json'));

        $wwPackQuoteTemplateUuid = '103167de-757d-49e5-aec1-33e4ea087a7a';
        $wwPackQuoteTemplateSchema = file_get_contents(database_path('seeders/models/ww_pack_epd_master_quote_template_schema.json'));

        $connection = $this->container['db.connection'];

        $vendors = $connection->table('vendors')
            ->whereNull('deleted_at')
            ->whereIn('short_code', ['CIS', 'LEN', 'IBM', 'HPE'])
            ->pluck('id')
            ->all();

        $countries = $connection->table('countries')
            ->whereNull('deleted_at')
            ->whereIn('iso_3166_2', ['AT', 'BE', 'CA', 'DK', 'FR', 'DE', 'IE', 'NL', 'NO', 'ZA', 'SE', 'CH', 'GB', 'US'])
            ->pluck('id')
            ->all();

        $connection->transaction(function () use ($wwPackQuoteTemplateSchema, $wwContractQuoteTemplateSchema, $wwPackQuoteTemplateUuid, $wwContractQuoteTemplateUuid, $vendors, $countries, $connection) {

            $connection->table('quote_templates')
                ->insertOrIgnore([
                    'id' => $wwContractQuoteTemplateUuid,
                    'name' => 'WW-Contract-EPD-Master',
                    'company_id' => $connection->table('companies')->where('short_code', 'EPD')->value('id'),
                    'business_division_id' => BD_WORLDWIDE,
                    'contract_type_id' => CT_CONTRACT,
                    'currency_id' => $connection->table('currencies')->where('code', 'GBP')->value('id'),
                    'form_data' => $wwContractQuoteTemplateSchema,
                    'is_system' => true,
                    'activated_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

            foreach ($countries as $countryKey) {

                $connection->table('country_quote_template')
                    ->insertOrIgnore([
                        'quote_template_id' => $wwContractQuoteTemplateUuid,
                        'country_id' => $countryKey
                    ]);
            }

            foreach ($vendors as $vendorKey) {

                $connection->table('quote_template_vendor')
                    ->insertOrIgnore([
                        'quote_template_id' => $wwContractQuoteTemplateUuid,
                        'vendor_id' => $vendorKey
                    ]);
            }


            $connection->table('quote_templates')
                ->insertOrIgnore([
                    'id' => $wwPackQuoteTemplateUuid,
                    'name' => 'WW-Pack-EPD-Master',
                    'company_id' => $connection->table('companies')->where('short_code', 'EPD')->value('id'),
                    'business_division_id' => BD_WORLDWIDE,
                    'contract_type_id' => CT_PACK,
                    'currency_id' => $connection->table('currencies')->where('code', 'GBP')->value('id'),
                    'form_data' => $wwPackQuoteTemplateSchema,
                    'is_system' => true,
                    'activated_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

            foreach ($countries as $countryKey) {

                $connection->table('country_quote_template')
                    ->insertOrIgnore([
                        'quote_template_id' => $wwPackQuoteTemplateUuid,
                        'country_id' => $countryKey
                    ]);
            }

            foreach ($vendors as $vendorKey) {

                $connection->table('quote_template_vendor')
                    ->insertOrIgnore([
                        'quote_template_id' => $wwPackQuoteTemplateUuid,
                        'vendor_id' => $vendorKey
                    ]);
            }

        });
    }
}
