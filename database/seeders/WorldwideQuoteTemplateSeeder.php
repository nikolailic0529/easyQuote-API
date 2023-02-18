<?php

namespace Database\Seeders;

use App\Domain\Company\Models\Company;
use App\Domain\Image\Services\ThumbHelper;
use Illuminate\Database\Seeder;

class WorldwideQuoteTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function run()
    {
        /** @var \App\Domain\Company\Models\Company $epdCompanyModel */
        $epdCompanyModel = Company::query()->where('short_code', 'EPD')->sole();
        $templateAssets = ThumbHelper::retrieveLogoFromModels([$epdCompanyModel], ThumbHelper::MAP);

        $oldWwContractQuoteTemplateUuid = '4f8bc11b-6109-41db-87f9-962c37dd8c7f';
        $oldWwContractQuoteTemplateSchema = file_get_contents(database_path('seeders/models/ww_contract_epd_master_quote_template_schema_old.json'));
        $oldWwContractQuoteTemplateSchema = self::substituteTemplateSchemaAssets($oldWwContractQuoteTemplateSchema, $templateAssets);

        $oldWwPackQuoteTemplateUuid = '103167de-757d-49e5-aec1-33e4ea087a7a';
        $oldWwPackQuoteTemplateSchema = file_get_contents(database_path('seeders/models/ww_pack_epd_master_quote_template_schema_old.json'));
        $oldWwPackQuoteTemplateSchema = self::substituteTemplateSchemaAssets($oldWwPackQuoteTemplateSchema, $templateAssets);

        $wwContractQuoteTemplateUuid = '64a3baf6-c557-4ce5-ace4-5f20061f843c';
        $wwContractQuoteTemplateSchema = file_get_contents(database_path('seeders/models/ww_contract_epd_master_quote_template_schema.json'));
        $wwContractQuoteTemplateSchema = self::substituteTemplateSchemaAssets($wwContractQuoteTemplateSchema, $templateAssets);

        $wwPackQuoteTemplateUuid = '085eaed6-efcc-462b-9319-94d85cdfe7bb';
        $wwPackQuoteTemplateSchema = file_get_contents(database_path('seeders/models/ww_pack_epd_master_quote_template_schema.json'));
        $wwPackQuoteTemplateSchema = self::substituteTemplateSchemaAssets($wwPackQuoteTemplateSchema, $templateAssets);

        $dataHeaders = file_get_contents(database_path('seeders/models/ww_master_quote_template_data_headers.json'));

        $connection = $this->container['db.connection'];

        $vendors = $connection->table('vendors')
            ->whereNull('deleted_at')
            ->whereIn('short_code', ['CIS', 'LEN', 'IBM', 'HPE', 'VMW', 'FUJ'])
            ->pluck('id')
            ->all();

        $countries = $connection->table('countries')
            ->whereNull('deleted_at')
            ->whereIn('iso_3166_2', ['AT', 'BE', 'CA', 'DK', 'FR', 'DE', 'IE', 'NL', 'NO', 'ZA', 'SE', 'CH', 'GB', 'US'])
            ->pluck('id')
            ->all();

        $seeds = [
            [
                'id' => $wwContractQuoteTemplateUuid,
                'name' => 'WW-Contract-EPD-Master [New Layout]',
                'company_id' => $connection->table('companies')->where('short_code', 'EPD')->value('id'),
                'business_division_id' => BD_WORLDWIDE,
                'contract_type_id' => CT_CONTRACT,
                'currency_id' => $connection->table('currencies')->where('code', 'GBP')->value('id'),
                'form_data' => $wwContractQuoteTemplateSchema,
                'data_headers' => $dataHeaders,
                'is_system' => true,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'countries' => $countries,
                'vendors' => $vendors,
            ],
            [
                'id' => $wwPackQuoteTemplateUuid,
                'name' => 'WW-Pack-EPD-Master [New Layout]',
                'company_id' => $connection->table('companies')->where('short_code', 'EPD')->value('id'),
                'business_division_id' => BD_WORLDWIDE,
                'contract_type_id' => CT_PACK,
                'currency_id' => $connection->table('currencies')->where('code', 'GBP')->value('id'),
                'form_data' => $wwPackQuoteTemplateSchema,
                'data_headers' => $dataHeaders,
                'is_system' => true,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'countries' => $countries,
                'vendors' => $vendors,
            ],
            [
                'id' => $oldWwContractQuoteTemplateUuid,
                'name' => 'WW-Contract-EPD-Master',
                'company_id' => $connection->table('companies')->where('short_code', 'EPD')->value('id'),
                'business_division_id' => BD_WORLDWIDE,
                'contract_type_id' => CT_CONTRACT,
                'currency_id' => $connection->table('currencies')->where('code', 'GBP')->value('id'),
                'form_data' => $oldWwContractQuoteTemplateSchema,
                'data_headers' => $dataHeaders,
                'is_system' => true,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'countries' => $countries,
                'vendors' => $vendors,
            ],
            [
                'id' => $oldWwPackQuoteTemplateUuid,
                'name' => 'WW-Pack-EPD-Master',
                'company_id' => $connection->table('companies')->where('short_code', 'EPD')->value('id'),
                'business_division_id' => BD_WORLDWIDE,
                'contract_type_id' => CT_PACK,
                'currency_id' => $connection->table('currencies')->where('code', 'GBP')->value('id'),
                'form_data' => $oldWwPackQuoteTemplateSchema,
                'data_headers' => $dataHeaders,
                'is_system' => true,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'countries' => $countries,
                'vendors' => $vendors,
            ],
        ];

        $connection->transaction(function () use ($connection, $seeds) {
            foreach ($seeds as $seed) {
                $connection->table('quote_templates')
                    ->upsert([
                        'id' => $seed['id'],
                        'name' => $seed['name'],
                        'company_id' => $seed['company_id'],
                        'business_division_id' => $seed['business_division_id'],
                        'contract_type_id' => $seed['contract_type_id'],
                        'currency_id' => $seed['currency_id'],
                        'form_data' => $seed['form_data'],
                        'data_headers' => $seed['data_headers'],
                        'is_system' => $seed['is_system'],
                        'activated_at' => $seed['activated_at'],
                        'created_at' => $seed['created_at'],
                        'updated_at' => $seed['updated_at'],
                    ], null, [
                        'name' => $seed['name'],
                        'company_id' => $seed['company_id'],
                        'currency_id' => $seed['currency_id'],
                        'form_data' => $seed['form_data'],
                        'data_headers' => $seed['data_headers'],
                        'is_system' => $seed['is_system'],
                    ]);

                foreach ($seed['countries'] as $key) {
                    $connection->table('country_quote_template')
                        ->insertOrIgnore([
                            'quote_template_id' => $seed['id'],
                            'country_id' => $key,
                        ]);
                }

                foreach ($seed['vendors'] as $key) {
                    $connection->table('quote_template_vendor')
                        ->insertOrIgnore([
                            'quote_template_id' => $seed['id'],
                            'vendor_id' => $key,
                        ]);
                }
            }
        });
    }

    protected static function substituteTemplateSchemaAssets(string $templateSchema, array $templateAssets): string
    {
        return preg_replace_callback('/{{(.*)}}/m', static function (array $item) use ($templateAssets) {
            return $templateAssets[last($item)] ?? null;
        }, $templateSchema);
    }
}
