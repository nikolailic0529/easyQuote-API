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
        Schema::create('data_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->nullable()->comment('Foreign key to users table')
                ->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('company_id')->nullable()->comment('Foreign key to companies table')
                ->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('business_division_id')->nullable()->comment('Foreign key to business_divisions table')
                ->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('file_id')->nullable()->comment('Foreign key to data_allocation_files table')
                ->constrained('data_allocation_files')->nullOnDelete()->cascadeOnUpdate();

            $table->date('assignment_start_date')->nullable()->comment('Assignment start date');
            $table->date('assignment_end_date')->nullable()->comment('Assignment end date');

            $table->string('distribution_algorithm')->default('Evenly')->comment('Distribution algorithm');

            $table->string('stage')->default('Import')->comment('Allocation stage');

            $table->timestamps();
            $table->softDeletes()->index();
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

        Schema::dropIfExists('data_allocations');

        Schema::enableForeignKeyConstraints();
    }
};
