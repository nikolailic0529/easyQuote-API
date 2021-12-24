<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReplicatedContactIdContactOpportunityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contact_opportunity', function (Blueprint $table) {
            $table->uuid('replicated_contact_id')->nullable()->after('contact_id')->comment('Replicated from contact ID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contact_opportunity', function (Blueprint $table) {
            $table->dropColumn('replicated_contact_id');
        });
    }
}
