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
        Schema::create('imported_companies', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('company_name', 250)->comment('Company Name');
            $table->string('company_category')->nullable()->comment('Company Category');
            $table->string('email')->nullable()->comment('Company Email');
            $table->string('phone')->nullable()->comment('Company Phone');
            $table->string('website')->nullable()->comment('Company Website');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('imported_companies');

        Schema::enableForeignKeyConstraints();
    }
};
