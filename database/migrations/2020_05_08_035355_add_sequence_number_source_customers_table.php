<?php

use App\Domain\Rescue\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSequenceNumberSourceCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('sequence_number')->index()->default(0)->after('rfq')->comment('Customer sequence number for RFQ number');
            $table->string('source')->index()->after('sequence_number')->comment('Determines whether customer belongs to the specific source');
        });

        DB::transaction(fn () => DB::table('customers')->update(['source' => Customer::S4_SOURCE])
        );

        DB::transaction(fn () => DB::table('quotes')->update(['cached_relations->customer->source' => Customer::S4_SOURCE])
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropIndex(['sequence_number']);

            $table->dropColumn(['sequence_number', 'source']);
        });
    }
}
