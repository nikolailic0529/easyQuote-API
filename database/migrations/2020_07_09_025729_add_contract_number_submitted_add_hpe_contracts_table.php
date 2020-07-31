<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddContractNumberSubmittedAddHpeContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        DB::table('hpe_contracts')->truncate();

        Schema::enableForeignKeyConstraints();

        Schema::table('hpe_contracts', function (Blueprint $table) {
            $table->char('contract_number', 14)->after('hpe_contract_file_id')->comment('HPE Contract Number');
            $table->unsignedBigInteger('sequence_number')->default(0)->after('contract_number')->comment('Incrementable Sequence Number');

            $table->timestamp('submitted_at')->nullable()->after('deleted_at')->comment('Contract Submission Timestamp');
            $table->timestamp('activated_at')->nullable()->after('submitted_at')->useCurrent()->comment('Contract Activation Timestamp');

            $table->index('submitted_at');
            $table->index('activated_at');

            $table->unique(['sequence_number', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hpe_contracts', function (Blueprint $table) {
            $table->dropUnique(['sequence_number', 'deleted_at']);

            $table->dropIndex(['submitted_at']);
            $table->dropIndex(['activated_at']);

            $table->dropColumn([
                'contract_number',
                'sequence_number',
                'submitted_at',
                'activated_at',
            ]);
        });
    }
}
