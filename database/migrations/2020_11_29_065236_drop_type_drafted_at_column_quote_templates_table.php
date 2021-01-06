<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropTypeDraftedAtColumnQuoteTemplatesTable extends Migration
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

        Schema::table('quote_templates', function (Blueprint $table) use ($schemaManager) {
            $indexes = collect($schemaManager->listTableIndexes('quote_templates'))->keys();
            $columns = collect($schemaManager->listTableColumns('quote_templates'))->keys();

            if ($indexes->contains('quote_templates_id_type_unique')) {
                $table->dropUnique(['id', 'type']);
            }

            if ($columns->contains('drafted_at')) {
                $table->dropColumn('drafted_at');
            }

            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quote_templates', function (Blueprint $table) {
            $table->unsignedSmallInteger('type')->default(0)->after('is_system');

            $table->timestamp('drafted_at')->nullable()->after('activated_at');

            $table->unique(['id', 'type']);
        });
    }
}
