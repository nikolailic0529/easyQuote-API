<?php

use Illuminate\Database\Seeder;
use App\Models \ {
    Vendor,
    Company
};

class CompaniesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Empty the companies table
        Schema::disableForeignKeyConstraints();

        DB::table('companies')->delete();

        Schema::enableForeignKeyConstraints();

        $companies = json_decode(file_get_contents(__DIR__ . '/models/companies.json'), true);

        collect($companies)->each(function ($company) {
            $companyId = (string) Uuid::generate(4);

            DB::table('companies')->insert([
                'id' => $companyId,
                'name' => $company['name'],
                'vat' => $company['vat'],
                'is_system' => true,
                'logo' => $company['logo']
            ]);

            collect($company['vendors'])->each(function ($vendorCode) use ($companyId) {
                $vendor = Vendor::where('short_code', $vendorCode)->first();

                Company::whereId($companyId)->first()->vendors()->attach($vendor);
            });
        });
    }
}
