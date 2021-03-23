<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContractTypeIdWorldwideQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->uuid('contract_type_id')->nullable()->after('id')->comment('Foreign key on contract_types table');
        });

        \Illuminate\Support\Facades\DB::table('worldwide_quotes')
                ->update([
                    'contract_type_id' => CT_CONTRACT
                ]);

        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->uuid('contract_type_id')->nullable(false)->change();

            $table->foreign('contract_type_id')->references('id')->on('contract_types')->cascadeOnDelete()->cascadeOnUpdate();
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

        Schema::table('worldwide_quotes', function (Blueprint $table) {
            $table->dropForeign(['contract_type_id']);
            $table->dropColumn('contract_type_id');
        });

        Schema::enableForeignKeyConstraints();
    }
}
