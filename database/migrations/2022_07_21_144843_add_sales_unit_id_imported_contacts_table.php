<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('imported_contacts', function (Blueprint $table) {
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

        Schema::table('imported_contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_unit_id');
        });

        Schema::enableForeignKeyConstraints();
    }
};
