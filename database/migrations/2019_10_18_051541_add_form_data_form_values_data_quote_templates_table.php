<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFormDataFormValuesDataQuoteTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_templates', function (Blueprint $table) {
            $table->json('form_data')->nullable();
            $table->json('form_values_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_templates', function (Blueprint $table) {
            $table->dropColumn([
                'form_data',
                'form_data_values'
            ]);
        });
    }
}
