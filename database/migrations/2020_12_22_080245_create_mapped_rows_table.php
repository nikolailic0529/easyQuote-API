<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMappedRowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mapped_rows', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('quote_file_id')->comment('Foreign key on quote_files table')->constrained()->onDeleteCascade()->onUpdateCascade();

            $table->string('product_no')->nullable();
            $table->string('description', 250)->nullable();
            $table->string('serial_no')->nullable();
            $table->date('date_from')->nullable();
            $table->date('date_to')->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->float('price')->default(0);
            $table->string('pricing_document', 250)->nullable();
            $table->string('system_handle', 250)->nullable();
            $table->string('searchable', 250)->nullable();
            $table->string('service_level_description', 250)->nullable();

            $table->boolean('is_selected')->default(false)->comment('Whether the row is selected');
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

        Schema::dropIfExists('mapped_rows');

        Schema::enableForeignKeyConstraints();
    }
}
