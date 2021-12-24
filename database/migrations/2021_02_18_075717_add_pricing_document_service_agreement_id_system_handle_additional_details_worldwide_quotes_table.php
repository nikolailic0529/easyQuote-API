<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPricingDocumentServiceAgreementIdSystemHandleAdditionalDetailsWorldwideQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->string('pricing_document', 1000)->nullable()->after('closing_date')->comment('Pack Quote Pricing Document Number');
            $table->string('service_agreement_id', 1000)->nullable()->after('pricing_document')->comment('Pack Quote Service Agreement ID');
            $table->string('system_handle', 1000)->nullable()->after('service_agreement_id')->comment('Pack Quote System Handle');
            $table->text('additional_details')->nullable()->after('system_handle')->comment('Pack Quote Additional Details');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->dropColumn([
                'pricing_document',
                'service_agreement_id',
                'system_handle',
                'additional_details',
            ]);
        });
    }
}
