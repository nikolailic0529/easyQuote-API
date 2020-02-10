<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangePricingDocumentServiceAgreementIdSystemHandleQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->text('pricing_document')->change();
            $table->text('system_handle')->change();
            $table->text('service_agreement_id')->change();
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
            $table->string('pricing_document')->change();
            $table->string('system_handle')->change();
            $table->string('service_agreement_id')->change();
        });
    }
}
