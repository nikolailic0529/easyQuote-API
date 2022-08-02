<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
//        DB::connection($this->getConnection())
//            ->statement(
//                'alter table custom_fields alter column is_not_deleted set invisible'
//            );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
//        DB::connection($this->getConnection())
//            ->statement(
//                'alter table custom_fields alter column is_not_deleted set visible'
//            );
    }
};
