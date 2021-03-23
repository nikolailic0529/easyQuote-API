<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHardwareAddressIdSoftwareAddressIdWorldwideCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worldwide_customers', function (Blueprint $table) {
            $table->foreignUuid('hardware_address_id')->nullable()->after('country_id')->comment('Foreign key on addresses table')->constrained('addresses')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('software_address_id')->nullable()->after('hardware_address_id')->comment('Foreign key on addresses table')->constrained('addresses')->nullOnDelete()->cascadeOnUpdate();
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

        Schema::table('worldwide_customers', function (Blueprint $table) {
            $table->dropForeign(['hardware_address_id']);
            $table->dropForeign(['software_address_id']);

            $table->dropColumn([
                'hardware_address_id',
                'software_address_id',
            ]);
        });

        Schema::enableForeignKeyConstraints();
    }
}
