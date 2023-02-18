<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateContractTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type_name')->comment('Contract Type Name');
            $table->string('type_short_name')->comment('Contract Type Short Name');
        });

        DB::transaction(function () {
            DB::table('contract_types')->insert([
                ['id' => 'c4da2cab-7fd0-4f60-87df-2cc9ea602fee', 'type_name' => 'Fixed Package Service', 'type_short_name' => 'Pack'],
                ['id' => 'c3c9d470-cb8b-48a2-9d3f-3614534b24a3', 'type_name' => 'Services Contract', 'type_short_name' => 'Contract'],
            ]);
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

        Schema::dropIfExists('contract_types');

        Schema::enableForeignKeyConstraints();
    }
}
