<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('imported_companies', function (Blueprint $table) {
            $table->string('picture_filename', 250)->nullable()->after('vendors_cs')->comment('Filename of company picture');
            $table->string('picture_url', 250)->nullable()->after('picture_filename')->comment('Url to company picture');
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
                'picture_filename',
                'picture_url'
            ]);
        });
    }
};
