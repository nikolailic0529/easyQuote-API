<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDatetimeTypeSystemSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $table->set('type', ['string', 'integer', 'array', 'datetime'])->default('string');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('system_settings', function (Blueprint $table) {
            $table->set('type', ['string', 'integer', 'array'])->default('string');
        });
    }
}
