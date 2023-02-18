<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('imported_companies', function (Blueprint $table) {
            $table->string('vat')->nullable()->after('company_category')->comment('Company VAT');
            $table->string('vat_type')->nullable()->after('vat')->comment('EXEMPT, NO VAT, VAT Number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('imported_companies', function (Blueprint $table) {
            $table->dropColumn([
                'vat',
                'vat_type',
            ]);
        });
    }
};
