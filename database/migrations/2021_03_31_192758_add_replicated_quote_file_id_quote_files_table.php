<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReplicatedQuoteFileIdQuoteFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_files', function (Blueprint $table) {
            $table->uuid('replicated_quote_file_id')->nullable()->after('id')->comment('Replicated Quote File from ID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_files', function (Blueprint $table) {
            $table->dropColumn('replicated_quote_file_id');
        });
    }
}
