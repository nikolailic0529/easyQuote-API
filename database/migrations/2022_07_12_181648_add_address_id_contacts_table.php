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
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignUuid('address_id')
                ->nullable()
                ->after('id')
                ->comment('Foreign key to addresses table')
                ->constrained()
                ->nullOnDelete()->cascadeOnUpdate();
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

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('address_id');
        });

        Schema::enableForeignKeyConstraints();
    }
};
