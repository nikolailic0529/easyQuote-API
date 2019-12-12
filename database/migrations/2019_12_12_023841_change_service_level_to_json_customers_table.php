<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeServiceLevelToJsonCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $customers = DB::select("select `id`, `service_level` from `customers`");

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('service_level');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->json('service_level')->nullable();
        });

        DB::transaction(function () use ($customers) {
            foreach ($customers as $customer) {
                $service_level = json_encode([['service_level' => $customer->service_level]]);
                DB::table('customers')->whereId($customer->id)->update(compact('service_level'));
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('service_level');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('service_level')->nullable();
        });
    }
}
