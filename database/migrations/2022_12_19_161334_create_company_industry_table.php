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
        Schema::create('company_industry', function (Blueprint $table) {
            $table->foreignUuid('company_id')
                ->comment('Foreign key to companies table')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('industry_id')
                ->comment('Foreign key to industries table')
                ->constrained('industries')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->primary(['company_id', 'industry_id']);
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

        Schema::dropIfExists('company_industry');

        Schema::enableForeignKeyConstraints();
    }
};
