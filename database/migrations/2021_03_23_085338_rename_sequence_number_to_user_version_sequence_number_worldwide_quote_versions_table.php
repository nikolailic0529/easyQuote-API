<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameSequenceNumberToUserVersionSequenceNumberWorldwideQuoteVersionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
            $table->dropForeign(['worldwide_quote_id']);
            $table->dropUnique('worldwide_quote_versions_sequence_unique');
        });

        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
           $table->foreign('worldwide_quote_id')->references('id')->on('worldwide_quotes')->cascadeOnDelete()->cascadeOnUpdate();

           $table->renameColumn('sequence_number', 'user_version_sequence_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_quote_versions', function (Blueprint $table) {
            $table->renameColumn('user_version_sequence_number', 'sequence_number');

            $table->unique(['worldwide_quote_id', 'sequence_number', 'deleted_at'], 'worldwide_quote_versions_sequence_unique');
        });
    }
}
