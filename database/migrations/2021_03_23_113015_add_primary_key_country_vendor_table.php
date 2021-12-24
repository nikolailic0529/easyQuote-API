<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrimaryKeyCountryVendorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /** @var \Doctrine\DBAL\Schema\DB2SchemaManager */
        $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();

        $indexes = $schemaManager->listTableIndexes('country_vendor');

        Schema::table('country_vendor', function (Blueprint $table) use ($indexes) {

            $table->dropForeign(['country_id']);
            $table->dropForeign(['vendor_id']);

            if (isset($indexes['primary'])) {
                $table->dropPrimary();
            }

            $table->primary(['country_id', 'vendor_id']);

        });

        Schema::table('country_vendor', function (Blueprint $table) {
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('country_vendor', function (Blueprint $table) {

            $table->dropForeign(['country_id']);
            $table->dropForeign(['vendor_id']);

            $table->dropPrimary();

            $table->primary(['country_id', 'vendor_id']);

        });

        Schema::table('country_vendor', function (Blueprint $table) {
            $table->foreign('country_id')->references('id')->on('countries')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
}
