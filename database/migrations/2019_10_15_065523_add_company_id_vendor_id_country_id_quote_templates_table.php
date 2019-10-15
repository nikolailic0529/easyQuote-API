<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCompanyIdVendorIdCountryIdQuoteTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_templates', function (Blueprint $table) {
            $table->uuid('company_id')->nullable();
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
            $table->uuid('vendor_id')->nullable();
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            $table->timestamp('activated_at')->nullable();
        });

        Schema::dropIfExists('company_quote_template');
        Schema::dropIfExists('vendor_quote_template');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_templates', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['vendor_id']);
            $table->dropColumn([
                'company_id',
                'vendor_id'
            ]);
        });

        Schema::create('company_quote_template', function (Blueprint $table) {
            $table->uuid('company_id');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->uuid('quote_template_id');
            $table->foreign('quote_template_id')->references('id')->on('quote_templates')->onDelete('cascade');
        });

        Schema::create('vendor_quote_template', function (Blueprint $table) {
            $table->uuid('vendor_id');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->uuid('quote_template_id');
            $table->foreign('quote_template_id')->references('id')->on('quote_templates')->onDelete('cascade');
        });
    }
}
