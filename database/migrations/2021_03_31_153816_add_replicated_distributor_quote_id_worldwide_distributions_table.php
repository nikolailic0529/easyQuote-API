<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReplicatedDistributorQuoteIdWorldwideDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->uuid('replicated_distributor_quote_id')->nullable()->after('id')->comment('Replicated from Distributor Quote ID');
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
            $table->dropColumn('replicated_distributor_quote_id');
        });
    }
}
