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
        Schema::table('opportunities', function (Blueprint $table) {
            $table->foreignUuid('imported_primary_account_id')
                ->nullable()
                ->after('primary_account_id')
                ->comment('Foreign key on imported_companies table')
                ->constrained('imported_companies')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignUuid('imported_primary_account_contact_id')
                ->nullable()
                ->after('primary_account_contact_id')
                ->comment('Foreign key on imported_contacts table')
                ->constrained('imported_contacts')
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

        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropConstrainedForeignId('imported_primary_account_id');
            $table->dropConstrainedForeignId('imported_primary_account_contact_id');
        });

        Schema::enableForeignKeyConstraints();
    }
};
