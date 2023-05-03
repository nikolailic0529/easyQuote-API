<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::drop('quote_notes');

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('quote_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('user_id')->comment('Foreign key to users table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('quote_id')->comment('Foreign key to quotes table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('quote_version_id')->nullable()->comment('Foreign key to quote_versions table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->boolean('is_from_quote')->default(0)->comment('Whether the note is from quote');

            $table->text('text');

            $table->timestamps();
            $table->softDeletes()->index();
        });
    }
};
