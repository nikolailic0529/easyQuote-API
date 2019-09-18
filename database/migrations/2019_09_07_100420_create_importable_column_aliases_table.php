<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImportableColumnAliasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('importable_column_aliases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('alias', 50);
            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->uuid('importable_column_id');
            $table->foreign('importable_column_id')->references('id')->on('importable_columns');
            $table->timestamps();
            $table->timestamp('drafted_at')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('importable_column_aliases');
    }
}
