<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('name');
            $table->string('rfq', 40);

            $table->dateTime('valid_until');
            $table->dateTime('support_start');
            $table->dateTime('support_end');

            $table->string('payment_terms')->nullable();
            $table->string('invoicing_terms')->nullable();
            $table->json('service_levels')->nullable();
            
            $table->timestamps();
            $table->softDeletes()->index();
            $table->timestamp('submitted_at')->index()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
