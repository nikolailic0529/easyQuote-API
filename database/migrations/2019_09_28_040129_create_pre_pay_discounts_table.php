<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePrePayDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pre_pay_discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 60);
            $table->json('durations');
            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->uuid('country_id');
            $table->foreign('country_id')->references('id')->on('countries');
            $table->uuid('vendor_id');
            $table->foreign('vendor_id')->references('id')->on('vendors');
            $table->timestamps();
            $table->timestamp('drafted_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pre_pay_discounts');
    }
}
