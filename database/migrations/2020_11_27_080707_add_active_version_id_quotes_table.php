<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActiveVersionIdQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignUuid('active_version_id')->nullable()->after('id')->comment('Active Version foreign key on quote_versions table')
                ->constrained('quote_versions')->nullOnDelete()->cascadeOnUpdate();
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
        
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['active_version_id']);
            $table->dropColumn('active_version_id');
        });

        Schema::enableForeignKeyConstraints();
    }
}
