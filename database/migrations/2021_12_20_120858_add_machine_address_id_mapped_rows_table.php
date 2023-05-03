<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mapped_rows', function (Blueprint $table) {
            $table->foreignUuid('machine_address_id')->nullable()->after('quote_file_id')->comment('Foreign key on addresses table')
                ->constrained('addresses')
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

        Schema::table('mapped_rows', function (Blueprint $table) {
            $table->dropConstrainedForeignId('machine_address_id');
        });

        Schema::enableForeignKeyConstraints();
    }
};
