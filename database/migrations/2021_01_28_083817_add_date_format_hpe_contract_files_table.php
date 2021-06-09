<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDateFormatHpeContractFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hpe_contract_files', function (Blueprint $table) {
            $table->string('date_format')->nullable()->after('original_file_name')->comment('Date Format used in the file');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hpe_contract_files', function (Blueprint $table) {
            $table->dropColumn('date_format');
        });
    }
}
