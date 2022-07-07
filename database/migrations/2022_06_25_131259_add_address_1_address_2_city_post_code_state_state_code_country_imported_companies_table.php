<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('imported_companies', function (Blueprint $table) {
            $table->string('address_1')->nullable()->after('website')->comment('Company Address One');
            $table->string('address_2')->nullable()->after('address_1')->comment('Company Address Two');
            $table->string('city')->nullable()->after('address_2')->comment('Company City');
            $table->string('post_code')->nullable()->after('city')->comment('Company Post Code');
            $table->string('state')->nullable()->after('post_code')->comment('Company State');
            $table->string('state_code')->nullable()->after('state')->comment('Company State Code');
            $table->string('country_name')->nullable()->after('state_code')->comment('Company Country');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('imported_companies', function (Blueprint $table) {
            $table->dropColumn([
                'address_1',
                'address_2',
                'city',
                'post_code',
                'state',
                'state_code',
                'country_name',
            ]);
        });
    }
};
