<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        Schema::drop('worldwide_quote_notes');

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('worldwide_quote_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('worldwide_quote_id')->nullable()->comment('Foreign key on worldwide_quotes table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('worldwide_quote_version_id')->nullable()->comment('Foreign key on worldwide_quote_versions table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('user_id')->nullable()->comment('Foreign key on users table')->constrained()->nullOnDelete()->cascadeOnUpdate();
            $table->boolean('is_for_submitted_quote')->default(0)->comment('Whether the note was created on quote submission');

            $table->text('text')->comment('Quote Note Text');

            $table->timestamps();
            $table->softDeletes()->index();
        });
    }
};
