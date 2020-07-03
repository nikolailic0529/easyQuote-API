<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuoteIdAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('assets', function (Blueprint $table) {
            /** @var \Illuminate\Database\Schema\ForeignKeyDefinition */
            $foreign = $table->foreignUuid('quote_id')->nullable()->after('vendor_id')->comment('Foreign key on Quotes table')->constrained('quotes');

            $foreign->onDelete('cascade')->onUpdate('cascade');
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['quote_id']);
            $table->dropColumn('quote_id');
        });

        Schema::enableForeignKeyConstraints();
    }
}
