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
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->foreignUuid('parent_field_id')->nullable()->after('id')->comment('Foreign key to custom_fields table')->constrained('custom_fields')->nullOnDelete()->cascadeOnUpdate();
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

        Schema::table('custom_fields', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_field_id');
        });

        Schema::enableForeignKeyConstraints();
    }
};
