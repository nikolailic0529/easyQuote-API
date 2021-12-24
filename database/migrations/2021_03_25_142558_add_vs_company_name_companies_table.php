<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVsCompanyNameCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('vs_company_code')->nullable()->after('short_code')->comment('Vendor-Services acceptable company name');
        });

        DB::transaction(function () {
            DB::table('companies')
                ->where('id', 'a79dd54d-0963-40cd-b878-58ccfe672016')
                ->update(['vs_company_code' => 'SWH']);

            DB::table('companies')
                ->where('id', 'b86170d9-ceca-4f58-8a04-99ed2b0acb87')
                ->update(['vs_company_code' => 'EPD']);

            DB::table('companies')
                ->where('id', 'ad8b3b8d-44ef-4c97-8b6d-cd69f030fb17')
                ->update(['vs_company_code' => 'TESTES']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('vs_company_code');
        });
    }
}
