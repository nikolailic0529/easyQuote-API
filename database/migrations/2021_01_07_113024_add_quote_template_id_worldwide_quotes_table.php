<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuoteTemplateIdWorldwideQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->foreignUuid('quote_template_id')->nullable()->after('company_id')->constrained()->nullOnDelete()->cascadeOnDelete();
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

        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->dropForeign(['quote_template_id']);
            $table->dropColumn('quote_template_id');
        });

        Schema::enableForeignKeyConstraints();
    }
}
