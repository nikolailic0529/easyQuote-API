<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCancelSalesOrderReasonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cancel_sales_order_reasons', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('description', 250)->comment('Reason description');

            $table->timestamps();
            $table->softDeletes()->index();
        });

        Artisan::call('db:seed', ['--class' => \Database\Seeders\CancelSalesOrderReasonSeeder::class]);


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('cancel_sales_order_reasons');

        Schema::enableForeignKeyConstraints();
    }
}
