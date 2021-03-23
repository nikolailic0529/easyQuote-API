<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTemplateFieldsTable extends Migration
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

        Schema::table('template_fields', function (Blueprint $table) use ($schemaManager) {
            $columns = $schemaManager->listTableColumns('template_fields');
            $indexes = $schemaManager->listTableIndexes('template_fields');

            if (isset($indexes['template_fields_activated_at_index'])) {
                $table->dropIndex(['activated_at']);
            }

            if (isset($indexes['template_fields_deleted_at_index'])) {
                $table->dropIndex(['deleted_at']);
            }

            if (isset($indexes['template_fields_user_id_foreign'])) {
                $table->dropForeign(['user_id']);
            }

            $table->dropColumn(
                array_intersect(array_keys($columns), ['default_value', 'is_column', 'user_id', 'created_at', 'updated_at', 'activated_at', 'drafted_at', 'deleted_at'])
            );
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

        Schema::table('template_fields', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->text('default_value')->nullable()->after('template_field_type_id');
            $table->boolean('is_column')->default(1)->after('is_system');

            $table->timestamps();
            $table->timestamp('activated_at')->nullable()->index();
            $table->timestamp('drafted_at')->nullable();

            $table->softDeletes()->index();
        });

        Schema::enableForeignKeyConstraints();
    }
}
