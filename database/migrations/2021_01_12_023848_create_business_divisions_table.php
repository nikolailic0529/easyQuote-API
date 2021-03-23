<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBusinessDivisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_divisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('division_name')->comment('Business Division Name');
        });

        DB::transaction(function () {

            DB::table('business_divisions')->insert([
                ['id' => '45fc3384-27c1-4a44-a111-2e52b072791e', 'division_name' => 'Rescue'],
                ['id' => 'f911cb0b-a1b0-4943-91e7-0a1c796984a1', 'division_name' => 'Worldwide']
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

        Schema::dropIfExists('business_divisions');

        Schema::enableForeignKeyConstraints();
    }
}
