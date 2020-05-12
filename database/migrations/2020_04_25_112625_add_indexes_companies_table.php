<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('vat')->nullable()->change();

            $table->index('name');

            $table->index(['type', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('vat')->nullable(false)->change();

            $table->dropIndex(['name']);

            $table->dropIndex(['type', 'category']);
        });
    }
}
