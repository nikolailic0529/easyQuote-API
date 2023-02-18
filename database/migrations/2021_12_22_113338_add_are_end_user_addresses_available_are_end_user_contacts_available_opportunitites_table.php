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
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropColumn('is_end_user_contact_data_missing');
            $table->boolean('are_end_user_addresses_available')->default(true)->after('account_manager_id')->comment('Whether addresses of end user are available');
            $table->boolean('are_end_user_contacts_available')->default(true)->after('are_end_user_addresses_available')->comment('Whether contacts of end user are available');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->boolean('is_end_user_contact_data_missing')->default(false)->after('account_manager_id')->comment('Whether the data of end user is missing');
            $table->dropColumn([
                'are_end_user_addresses_available',
                'are_end_user_contacts_available',
            ]);
        });
    }
};
