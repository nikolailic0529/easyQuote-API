<?php

namespace Database\Seeders;

use App\Domain\Company\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function run()
    {
        $companies = yaml_parse_file(__DIR__.'/models/companies.yaml');

        $connection = $this->container['db.connection'];

        $companies = array_map(function (array $company) use ($connection) {
            $vendorKeys = $connection->table('vendors')
                ->whereIn('short_code', $company['vendors'])
                ->pluck('id')
                ->all();

            $defaultVendorKey = $connection->table('vendors')
                ->where('short_code', $company['default_vendor'])
                ->value('id');

            return array_merge($company, [
                'vendor_model_keys' => $vendorKeys,
                'default_vendor_id' => $defaultVendorKey,
            ]);
        }, $companies);

        $connection->transaction(function () use ($connection, $companies) {
            foreach ($companies as $company) {
                $connection->table('companies')
                    ->insertOrIgnore([
                        'id' => $company['id'],
                        'name' => $company['name'],
                        'short_code' => $company['short_code'],
                        'vs_company_code' => $company['vs_company_code'],
                        'vat' => $company['vat'],
                        'type' => $company['type'],
                        'default_vendor_id' => $company['default_vendor_id'],
                        'email' => $company['email'],
                        'phone' => $company['phone'],
                        'website' => $company['website'],
                        'flags' => Company::SYSTEM | Company::SYNC_PROTECTED,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'activated_at' => now(),
                    ]);

                foreach ($company['vendor_model_keys'] as $vendorKey) {
                    $connection->table('company_vendor')
                        ->insertOrIgnore([
                            'company_id' => $company['id'],
                            'vendor_id' => $vendorKey,
                        ]);
                }
            }
        });
    }
}
