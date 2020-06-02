<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVatEmailPhoneCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone')->nullable()->comment('Customer Phone')->after('service_levels');
            $table->string('email')->nullable()->comment('Customer Email')->after('service_levels');
            $table->string('vat')->nullable()->comment('Customer VAT / Tax Number')->after('service_levels');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['vat', 'email', 'phone']);
        });
    }
}
