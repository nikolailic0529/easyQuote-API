<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpportunityTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('opportunity_totals', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->nullable()->comment('Foreign key on users table')->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('opportunity_id')->comment('Foreign key on opportunities table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->float('base_opportunity_amount')->comment('Opportunity Amount in Base Currency');

            $table->mediumInteger('opportunity_status')->comment('Opportunity Status');
            $table->timestamp('opportunity_created_at')->comment('Opportunity Created At Timestamp');

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

        Schema::dropIfExists('opportunity_totals');

        Schema::enableForeignKeyConstraints();
    }
}
