<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('company_id')->comment('Foreign key on companies table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->foreignUuid('user_id')->nullable()->comment('Foreign key on users table')->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->text('text')->nullable()->comment('Company Note Text');

            $table->timestamps();
            $table->softDeletes()->index();
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

        Schema::dropIfExists('company_notes');

        Schema::enableForeignKeyConstraints();
    }
}
