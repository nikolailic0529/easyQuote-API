<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDistributorFileIdScheduleFileIdQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignUuid('distributor_file_id')->nullable()->after('customer_id')->comment('Foreign key on quotes table')->constrained('quote_files')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('schedule_file_id')->nullable()->after('customer_id')->comment('Foreign key on quotes table')->constrained('quote_files')->nullOnDelete()->cascadeOnUpdate();
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

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropForeign(['distributor_file_id']);
            $table->dropForeign(['schedule_file_id']);
        });

        Schema::enableForeignKeyConstraints();
    }
}
