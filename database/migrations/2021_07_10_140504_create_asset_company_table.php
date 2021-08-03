<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetCompanyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_company', function (Blueprint $table) {
            $table->foreignUuid('asset_id')->comment('Foreign key on assets table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('company_id')->comment('Foreign key on companies table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['asset_id', 'company_id']);
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

        Schema::dropIfExists('asset_company');

        Schema::enableForeignKeyConstraints();
    }
}
