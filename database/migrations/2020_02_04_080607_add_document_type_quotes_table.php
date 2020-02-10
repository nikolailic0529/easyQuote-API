<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddDocumentTypeQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('document_type')->after('customer_id');
            $table->index(['id', 'document_type']);
        });

        DB::table('quotes')->update(['document_type' => Q_TYPE_QUOTE]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex(['id', 'document_type']);
            $table->dropColumn('document_type');
        });
    }
}
