<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteVersionFieldColumnTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_version_field_column', function (Blueprint $table) {
            $table->foreignUuid('quote_version_id')->comment('Foreign key on quote_versions table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('template_field_id')->comment('Foreign key on template_fields table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('importable_column_id')->nullable()->comment('Foreign key on importable_columns table')->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->boolean('is_default_enabled')->default(false)->comment('Whether is default field value enabled');
            $table->string('default_value', 250)->nullable()->comment('Field default value');

            $table->boolean('is_preview_visible')->default(true)->comment('Whether is field visible on preview screen');

            $table->string('sort')->nullable()->comment('Field sorting ascending/descending');

            $table->primary(['quote_version_id', 'template_field_id'], 'quote_version_field_column_primary');
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

        Schema::dropIfExists('quote_version_field_column');

        Schema::enableForeignKeyConstraints();
    }
}
