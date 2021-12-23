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
        Schema::table('opportunities', function (Blueprint $table) {
            $table->foreignUuid('end_user_id')->nullable()->after('primary_account_id')->comment('Foreign key on end_customers table')->constrained('companies')->nullOnDelete()->cascadeOnUpdate();
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
            $table->dropConstrainedForeignId('end_user_id');
        });

        Schema::enableForeignKeyConstraints();
    }
};
