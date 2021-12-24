<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrimaryKeyCompanyVendorTable extends Migration
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

        $indexes = $schemaManager->listTableIndexes('company_vendor');

        Schema::table('company_vendor', function (Blueprint $table) use ($indexes) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['vendor_id']);

            if (isset($indexes['primary'])) {
                $table->dropPrimary();
            }

            $table->primary(['company_id', 'vendor_id']);
        });

        Schema::table('company_vendor', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete()->cascadeOnUpdate();
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
        Schema::table('company_vendor', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['vendor_id']);

            $table->dropPrimary();

            $table->primary(['company_id', 'vendor_id']);
        });

        Schema::table('company_vendor', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('vendor_id')->references('id')->on('vendors')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
}
