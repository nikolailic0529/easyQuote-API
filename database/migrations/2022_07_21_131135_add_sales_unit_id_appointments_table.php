<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {


        DB::connection($this->getConnection())
            ->statement("
                ALTER TABLE appointments CHANGE user_id user_id CHAR(36) CHARACTER SET utf8mb3 DEFAULT NULL COLLATE `utf8_general_ci` COMMENT 'Foreign key to users table(DC2Type:guid)' AFTER id;
            ");

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignUuid('sales_unit_id')
                ->nullable()
                ->after('user_id')
                ->comment('Foreign key to sales_units table')
                ->constrained()
                ->nullOnDelete()
                ->cascadeOnUpdate();
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

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_unit_id');
        });

        Schema::enableForeignKeyConstraints();
    }
};
