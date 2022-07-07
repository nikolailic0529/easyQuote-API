<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('imported_addresses', function (Blueprint $table) {
            $table->foreignUuid('contact_id')->nullable()->after('id')->comment('Foreign key to imported_contacts table')
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

        Schema::table('imported_addresses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_id');
        });

        Schema::enableForeignKeyConstraints();
    }
};
