<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCollaborationIdUsersQuotesDiscountsCountryMarginsVendorsCompaniesQuoteTemplatesTemplateFieldsTables extends Migration
{
    /**
     * Tables
     *
     * @var array
     */
    protected $tables = [
        'users',
        'quotes',
        'quote_files',
        'multi_year_discounts',
        'pre_pay_discounts',
        'promotional_discounts',
        'sn_discounts',
        'country_margins',
        'vendors',
        'companies',
        'quote_templates',
        'template_fields',
        'importable_columns'
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->uuid('collaboration_id')->nullable();
                $table->foreign('collaboration_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropForeign(['collaboration_id']);
                $table->dropColumn('collaboration_id');
            });
        }
    }
}
