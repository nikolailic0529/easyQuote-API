<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdditionalQuoteDetailsFieldsQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('pricing_document')->nullable();
            $table->string('service_agreement_id')->nullable();
            $table->string('system_handle')->nullable();
            $table->text('additional_details')->nullable();
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
            $table->dropColumn([
                'pricing_document',
                'service_agreement_id',
                'system_handle',
                'additional_details'
            ]);
        });
    }
}
