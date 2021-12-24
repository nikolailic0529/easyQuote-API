<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorldwideQuoteVersionIdWorldwideQuoteNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quote_notes', function (Blueprint $table) {
            $table->foreignUuid('worldwide_quote_version_id')->nullable()->after('worldwide_quote_id')->comment('Foreign key on worldwide_quote_versions table')->constrained()->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worldwide_quote_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('worldwide_quote_version_id');
        });
    }
}
