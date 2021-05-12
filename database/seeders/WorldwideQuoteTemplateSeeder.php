<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Services\ThumbHelper;
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
        /** @var \App\Models\Company $epdCompanyModel */
        $epdCompanyModel = Company::query()->where('short_code', 'EPD')->sole();
        $templateAssets = ThumbHelper::retrieveLogoFromModels([$epdCompanyModel], ThumbHelper::WITH_KEYS);

        $wwContractQuoteTemplateUuid = '4f8bc11b-6109-41db-87f9-962c37dd8c7f';
        $wwContractQuoteTemplateSchema = file_get_contents(database_path('seeders/models/ww_contract_epd_master_quote_template_schema.json'));
        $wwContractQuoteTemplateSchema = self::substituteTemplateSchemaAssets($wwContractQuoteTemplateSchema, $templateAssets);

        $wwPackQuoteTemplateUuid = '103167de-757d-49e5-aec1-33e4ea087a7a';
        $wwPackQuoteTemplateSchema = file_get_contents(database_path('seeders/models/ww_pack_epd_master_quote_template_schema.json'));
        $wwPackQuoteTemplateSchema = self::substituteTemplateSchemaAssets($wwPackQuoteTemplateSchema, $templateAssets);

        $dataHeaders = file_get_contents(database_path('seeders/models/ww_master_quote_template_data_headers.json'));

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

        $connection->transaction(function () use ($dataHeaders, $wwPackQuoteTemplateSchema, $wwContractQuoteTemplateSchema, $wwPackQuoteTemplateUuid, $wwContractQuoteTemplateUuid, $vendors, $countries, $connection) {

            $connection->table('quote_templates')
                ->upsert([
                    'id' => $wwContractQuoteTemplateUuid,
                    'name' => 'WW-Contract-EPD-Master',
                    'company_id' => $connection->table('companies')->where('short_code', 'EPD')->value('id'),
                    'business_division_id' => BD_WORLDWIDE,
                    'contract_type_id' => CT_CONTRACT,
                    'currency_id' => $connection->table('currencies')->where('code', 'GBP')->value('id'),
                    'form_data' => $wwContractQuoteTemplateSchema,
                    'data_headers' => $dataHeaders,
                    'is_system' => true,
                    'activated_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ], null, [
                    'name' => 'WW-Contract-EPD-Master',
                    'company_id' => $connection->table('companies')->where('short_code', 'EPD')->value('id'),
                    'currency_id' => $connection->table('currencies')->where('code', 'GBP')->value('id'),
                    'form_data' => $wwContractQuoteTemplateSchema,
                    'data_headers' => $dataHeaders,
                    'is_system' => true,
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
                ->upsert([
                    'id' => $wwPackQuoteTemplateUuid,
                    'name' => 'WW-Pack-EPD-Master',
                    'company_id' => $connection->table('companies')->where('short_code', 'EPD')->value('id'),
                    'business_division_id' => BD_WORLDWIDE,
                    'contract_type_id' => CT_PACK,
                    'currency_id' => $connection->table('currencies')->where('code', 'GBP')->value('id'),
                    'form_data' => $wwPackQuoteTemplateSchema,
                    'data_headers' => $dataHeaders,
                    'is_system' => true,
                    'activated_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ], null, [
                    'name' => 'WW-Pack-EPD-Master',
                    'company_id' => $connection->table('companies')->where('short_code', 'EPD')->value('id'),
                    'currency_id' => $connection->table('currencies')->where('code', 'GBP')->value('id'),
                    'form_data' => $wwPackQuoteTemplateSchema,
                    'data_headers' => $dataHeaders,
                    'is_system' => true,
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

    protected static function substituteTemplateSchemaAssets(string $templateSchema, array $templateAssets)
    {
        return preg_replace_callback('/{{(.*)}}/m', function ($item) use ($templateAssets) {
            return $templateAssets[last($item)] ?? null;
        }, $templateSchema);
    }
}
