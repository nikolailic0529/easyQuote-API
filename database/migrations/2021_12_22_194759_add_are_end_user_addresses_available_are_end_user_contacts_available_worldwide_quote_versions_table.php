<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
            $table->boolean('are_end_user_addresses_available')->default(true)->after('use_groups')->comment('Whether end user addresses are available');
            $table->boolean('are_end_user_contacts_available')->default(true)->after('are_end_user_addresses_available')->comment('Whether end user contacts are available');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
            $table->dropColumn([
                'are_end_user_addresses_available',
                'are_end_user_contacts_available',
            ]);
        });
    }
};
