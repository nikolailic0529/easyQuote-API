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
        Schema::table('pipeliner_sync_errors', function (Blueprint $table) {
            $table->timestamp('resolved_at')->nullable()->after('archived_at');

            $table->index(['deleted_at', 'resolved_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pipeliner_sync_errors', function (Blueprint $table) {
            $table->dropIndex(['deleted_at', 'resolved_at']);

            $table->dropColumn('resolved_at');
        });
    }
};
