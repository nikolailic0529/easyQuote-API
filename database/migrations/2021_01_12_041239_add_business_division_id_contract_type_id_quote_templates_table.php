<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBusinessDivisionIdContractTypeIdQuoteTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quote_templates', function (Blueprint $table) {
            $table->uuid('business_division_id')->nullable()->after('user_id')->comment('Foreign key on business_divisions table');
            $table->uuid('contract_type_id')->nullable()->after('business_division_id')->comment('Foreign key on contract_types table');
        });

        DB::transaction(function () {

            DB::table('quote_templates')->update([
                'business_division_id' => '45fc3384-27c1-4a44-a111-2e52b072791e', // Rescue
                'contract_type_id' => 'c3c9d470-cb8b-48a2-9d3f-3614534b24a3' // Services Contract
            ]);

        });

        Schema::table('quote_templates', function (Blueprint $table) {
           $table->uuid('business_division_id')->nullable(false)->change();
           $table->uuid('contract_type_id')->nullable(false)->change();
        });

        Schema::table('quote_templates', function (Blueprint $table) {
            $table->foreign('business_division_id')->references('id')->on('business_divisions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('contract_type_id')->references('id')->on('contract_types')->cascadeOnDelete()->cascadeOnUpdate();
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

        Schema::table('quote_templates', function (Blueprint $table) {
            $table->dropForeign(['business_division_id']);
            $table->dropForeign(['contract_type_id']);

            $table->dropColumn([
                'business_division_id',
                'contract_type_id'
            ]);
        });

        Schema::enableForeignKeyConstraints();
    }
}
