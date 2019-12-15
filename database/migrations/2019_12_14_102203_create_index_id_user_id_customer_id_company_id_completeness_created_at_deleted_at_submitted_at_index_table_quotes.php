<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndexIdUserIdCustomerIdCompanyIdCompletenessCreatedAtDeletedAtSubmittedAtIndexTableQuotes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->index([
                'id',
                'user_id',
                'customer_id',
                'company_id',
                'completeness',
                'created_at',
                'deleted_at',
                'submitted_at',
                'activated_at'
            ], 'quotes_list_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropIndex('quotes_list_index');
        });
    }
}
