<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DropUserIdImportedRowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        $newTableName = 'imported_rows_'.Str::random();

        Schema::create($newTableName, function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('replicated_row_id')->nullable()->comment('Replicated row key');
            $table->foreignUuid('quote_file_id')->comment('Foreign key on quote files table')->constrained()->cascadeOnDelete()->cascadeOnDelete();

            $table->integer('page')->nullable()->comment('Imported Page number');

            $table->boolean('is_selected')->default(false)->comment('Whether the row is selected');
            $table->boolean('is_one_pay')->default(false)->comment('Whether the row is one pay');

            $table->json('columns_data')->nullable()->comment('Row data');

            $table->timestamps();
            $table->softDeletes()->index();

            $table->index(['deleted_at', 'is_selected']);
            $table->index('replicated_row_id');
        });

        DB::table($newTableName)->insertUsing(
            $columns = ['id', 'replicated_row_id', 'quote_file_id', 'columns_data', 'page', 'is_selected', 'is_one_pay', 'created_at', 'updated_at', 'deleted_at'],
            DB::table('imported_rows')->select($columns)
        );

        $oldTableName = 'imported_rows_'.Str::random();

        Schema::rename('imported_rows', $oldTableName); // keep a dump of the old table

        Schema::rename($newTableName, 'imported_rows');

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

        Schema::table('imported_rows', function (Blueprint $table) {
            $table->foreignUuid('user_id')->nullable()->comment('Foreign key on users table')->constrained()->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::enableForeignKeyConstraints();
    }
}
