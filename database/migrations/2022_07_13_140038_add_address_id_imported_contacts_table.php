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
            $table->foreignUuid('address_id')
                ->nullable()
                ->after('id')
                ->comment('Foreign key to imported_addresses table')
                ->constrained('imported_addresses')
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
            $table->dropConstrainedForeignId('address_id');
        });

        Schema::enableForeignKeyConstraints();
    }
};
