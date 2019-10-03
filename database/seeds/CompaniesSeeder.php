<?php

use Illuminate\Database\Seeder;
use App\Models \ {
    Vendor,
    Company,
    Image
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
                'is_system' => true
            ]);

            $createdCompany = Company::whereId($companyId)->first();

            collect($company['vendors'])->each(function ($vendorCode) use ($createdCompany) {
                $vendor = Vendor::where('short_code', $vendorCode)->first();

                $createdCompany->vendors()->attach($vendor);
            });

            $createdCompany->createImage($company['logo'], true);
        });
    }
}
