<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RearrangeColumnsQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('alter table `quotes` modify column `created_at` timestamp null after `sort_group_description`');
        DB::statement('alter table `quotes` modify column `updated_at` timestamp null after `created_at`');
        DB::statement('alter table `quotes` modify column `deleted_at` timestamp null after `updated_at`');
        DB::statement('alter table `quotes` modify column `activated_at` timestamp null after `deleted_at`');
        DB::statement('alter table `quotes` modify column `drafted_at` timestamp null after `activated_at`');
        DB::statement('alter table `quotes` modify column `submitted_at` timestamp null after `drafted_at`');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('alter table `quotes` modify column `created_at` timestamp null after `language_id`');
        DB::statement('alter table `quotes` modify column `updated_at` timestamp null after `created_at`');
        DB::statement('alter table `quotes` modify column `drafted_at` timestamp null after `updated_at`');
        DB::statement('alter table `quotes` modify column `deleted_at` timestamp null after `drafted_at`');
        DB::statement('alter table `quotes` modify column `activated_at` timestamp null after `completeness`');
        DB::statement('alter table `quotes` modify column `submitted_at` timestamp null after `buy_price`');
    }
}
