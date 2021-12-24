<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateHpeTemplateForeignKeyUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['hpe_contract_template_id']);

            $table->foreign('hpe_contract_template_id')->references('id')->on('hpe_contract_templates')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['hpe_contract_template_id']);

            $table->foreign('hpe_contract_template_id')->references('id')->on('quote_templates')->nullOnDelete()->cascadeOnUpdate();
        });
    }
}
