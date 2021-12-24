<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReplicatedAddressIdAddressWorldwideDistributionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('address_worldwide_distribution', function (Blueprint $table) {
            $table->uuid('replicated_address_id')->nullable()->after('address_id')->comment('Replicated from address ID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('address_worldwide_distribution', function (Blueprint $table) {
            $table->dropColumn('replicated_address_id');
        });
    }
}
