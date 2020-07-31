<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImportedAtHpeContractFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hpe_contract_files', function (Blueprint $table) {
            $table->timestamp('imported_at')->after('deleted_at')->nullable()->comment('Whether the file is imported');
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
            $table->dropColumn('imported_at');
        });
    }
}
