<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropQuoteTemplateIdWorldwideDistributionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->dropForeign(['quote_template_id']);
            $table->dropColumn('quote_template_id');
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

        Schema::table('worldwide_distributions', function (Blueprint $table) {
            $table->foreignUuid('quote_template_id')->nullable()->after('country_id')->comment('Foreign key on quote_templates table')->constrained()->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::enableForeignKeyConstraints();
    }
}
