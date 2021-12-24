<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOpportunityIdWorldwideQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->dropForeign(['worldwide_customer_id']);
            $table->uuid('worldwide_customer_id')->nullable()->change();

            $table->foreignUuid('opportunity_id')->after('id')->comment('Foreign key on opportunities table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
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
            $table->dropForeign(['opportunity_id']);
            $table->dropColumn('opportunity_id');

            $table->foreign('worldwide_customer_id')->references('id')->on('worldwide_customers')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
}
