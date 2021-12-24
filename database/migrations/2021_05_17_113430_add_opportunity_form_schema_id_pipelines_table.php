<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOpportunityFormSchemaIdPipelinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pipelines', function (Blueprint $table) {
            $table->foreignUuid('opportunity_form_schema_id')->nullable()->after('id')->comment('Foreign key on opportunity_form_schemas table')->constrained()->nullOnDelete()->cascadeOnUpdate();
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

        Schema::table('pipelines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('opportunity_form_schema_id');
        });

        Schema::enableForeignKeyConstraints();
    }
}
