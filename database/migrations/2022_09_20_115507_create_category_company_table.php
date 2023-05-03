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
        Schema::create('category_company', function (Blueprint $table) {
            $table->foreignUuid('company_id')
                ->comment('Foreign key to companies table')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignUuid('category_id')
                ->comment('Foreign key to company_categories table')
                ->constrained('company_categories')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->primary(['company_id', 'category_id']);
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

        Schema::dropIfExists('category_company');

        Schema::enableForeignKeyConstraints();
    }
};
