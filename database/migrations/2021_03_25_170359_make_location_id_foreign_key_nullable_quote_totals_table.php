<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeLocationIdForeignKeyNullableQuoteTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /** @var \Doctrine\DBAL\Schema\DB2SchemaManager */
        $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();

        $indexes = $schemaManager->listTableIndexes('quote_totals');

        Schema::table('quote_totals', function (Blueprint $table) use ($indexes) {
            if (isset($indexes['quote_totals_location_coordinates_spatial'])) {
                $table->dropIndex('quote_totals_location_coordinates_spatial');
            }

            if (isset($indexes['quote_totals_location_coordinates_spatialindex'])) {
                $table->dropIndex('quote_totals_location_coordinates_spatialindex');
            }

            $table->dropForeign(['location_id']);

            $table->uuid('location_id')->nullable()->change();

            $table->dropColumn('location_coordinates');
        });

        Schema::table('quote_totals', function (Blueprint $table) use ($indexes) {
            $table->string('location_address', 500)->nullable(true)->change();

            $table->point('location_coordinates')->nullable()->after('location_address');

            $table->foreign('location_id')->references('id')->on('locations')->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_totals', function (Blueprint $table) {
            $table->dropColumn('location_coordinates');

            $table->dropForeign(['location_id']);
        });

        Schema::table('quote_totals', function (Blueprint $table) {
            $table->string('location_address', 191)->nullable(false)->change();

            $table->point('location_coordinates')->nullable(false)->after('location_address');

            $table->spatialIndex(['location_coordinates']);

            $table->uuid('location_id')->nullable(false)->change();

            $table->foreign('location_id')->references('id')->on('locations')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
}
