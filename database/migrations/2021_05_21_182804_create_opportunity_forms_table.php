<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOpportunityFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('opportunity_forms', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->nullable()->comment('Foreign key on users table, Owner of the record')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('pipeline_id')->comment('Foreign key on pipelines')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('form_schema_id')->comment('Foreign key on template_schemas table')->constrained('opportunity_form_schemas')->cascadeOnDelete()->cascadeOnUpdate();

            $table->boolean('is_system')->default(0)->comment('Whether the record is system defined');

            $table->timestamps();
            $table->softDeletes()->index();
            $table->boolean('is_not_deleted')->virtualAs("IF(deleted_at IS NULL, 1, NULL)");

            $table->unique(['pipeline_id', 'is_not_deleted']);
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

        Schema::dropIfExists('opportunity_forms');

        Schema::enableForeignKeyConstraints();
    }
}
