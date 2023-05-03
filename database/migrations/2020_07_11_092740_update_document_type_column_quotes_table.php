<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateDocumentTypeColumnQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::update("UPDATE `quotes` SET `document_type` = CASE WHEN `document_type` = 'c' THEN ? ELSE ? END", [2, 1]);

        DB::statement("ALTER TABLE `quotes` CHANGE COLUMN `document_type` `document_type` TINYINT(1) NOT NULL COMMENT 'Determines whether it quote / contract / hpe-contract'");

        Schema::table('quotes', function (Blueprint $table) {
            $table->string('hpe_contract_number')->nullable()->after('document_type')->comment('HPE Contract Number');
            $table->string('hpe_contract_customer_name')->nullable()->after('hpe_contract_number')->comment('HPE Contract Customer Name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['hpe_contract_number', 'hpe_contract_customer_name']);
        });
    }
}
