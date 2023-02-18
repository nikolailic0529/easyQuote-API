<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     *
     * @noinspection SqlDialectInspection
     * @noinspection SqlNoDataSourceInspection
     */
    public function up()
    {
        DB::statement('alter table companies modify user_id char(36) null after id;');

        DB::statement("alter table companies modify sales_unit_id char(36) null comment 'Foreign key to sales_units table' after user_id;");

        DB::statement('alter table companies modify default_vendor_id char(36) null after sales_unit_id;');

        DB::statement('alter table companies modify default_country_id char(36) null after default_vendor_id;');

        DB::statement('alter table companies modify default_template_id char(36) null after default_country_id;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
