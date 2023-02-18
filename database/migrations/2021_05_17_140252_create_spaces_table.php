<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSpacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('space_name')->comment('Space Name');

            $table->timestamps();
        });

        DB::transaction(function () {
            DB::table('spaces')
                ->insertOrIgnore([
                    'id' => SP_EPD,
                    'space_name' => 'EPD',
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

        Schema::dropIfExists('spaces');

        Schema::enableForeignKeyConstraints();
    }
}
