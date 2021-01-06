<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropUnusedColumnsQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        /** @var \Doctrine\DBAL\Schema\DB2SchemaManager */
        $schemaManager = Schema::getConnection()->getDoctrineSchemaManager();

        Schema::table('quotes', function (Blueprint $table) use ($schemaManager) {
            $foreignKeys = collect($schemaManager->listTableIndexes('quotes'))->keys();
            $columns = collect($schemaManager->listTableColumns('quotes'))->keys();

            if ($foreignKeys->contains('quotes_language_id_foreign')) {
                $table->dropForeign(['language_id']);
            }

            $table->dropForeign(['quote_id']);
            $table->dropForeign(['hpe_contract_id']);

            $table->dropIndex(['document_type']);
            $table->dropIndex(['assets_migrated_at']);

            if ($columns->contains('language_id')) {
                $table->dropColumn('language_id');
            }

            if ($columns->contains('submitted_data')) {
                $table->dropColumn('submitted_data');
            }

            $table->dropColumn([
                'quote_id',
                'hpe_contract_id',
                'document_type',
                'hpe_contract_number',
                'hpe_contract_customer_name',
                'type',
                'margin_data',
                'cached_relations',
                'drafted_at',
                'is_version',
                'version_number',
            ]);
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
        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignUuid('language_id')->nullable()->after('country_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('quote_id')->nullable()->after('customer_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('hpe_contract_id')->nullable()->after('quote_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->unsignedTinyInteger('document_type')->after('hpe_contract_id')->index();
            $table->string('hpe_contract_number')->nullable()->after('document_type');
            $table->string('hpe_contract_customer_name')->nullable()->after('hpe_contract_number');
            $table->set('type', ['New', 'Renewal'])->nullable()->after('hpe_contract_customer_name');
            $table->json('margin_data')->nullable()->after('completeness');
            $table->json('submitted_data')->nullable()->after('group_description');
            $table->json('cached_relations')->nullable()->after('sort_group_description');
            $table->timestamp('drafted_at')->nullable()->after('activated_at');

            $table->boolean('is_version')->default(false)->after('sort_group_description');
            $table->unsignedInteger('version_number')->nullable()->after('is_version');

            $table->index('assets_migrated_at');
        });
    }
}
