<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_totals', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('quote_id');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('cascade');

            $table->decimal('total_price')->default(0);
            $table->string('rfq_number')->index();

            $table->timestamp('quote_created_at')->nullable();
            $table->timestamp('quote_submitted_at')->nullable();
            $table->timestamp('valid_until_date')->nullable();

            $table->index(['quote_created_at', 'quote_submitted_at', 'valid_until_date'], 'quote_totals_cat_sat_vud');

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
        Schema::dropIfExists('quote_totals');
    }
}
