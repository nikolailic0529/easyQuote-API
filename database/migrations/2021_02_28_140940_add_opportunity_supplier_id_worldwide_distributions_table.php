<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOpportunitySupplierIdWorldwideDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->foreignUuid('opportunity_supplier_id')->nullable()->after('worldwide_quote_id')->comment('Foreign key on opportunity suppliers table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->dropForeign(['opportunity_supplier_id']);
            $table->dropColumn('opportunity_supplier_id');
        });
    }
}
