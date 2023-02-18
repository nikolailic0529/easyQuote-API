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
        Schema::create('appointment_contact_invitees', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('pl_reference')->nullable()->comment('Reference to Contact Invitee in Pipeliner');
            $table->index('pl_reference');

            $table->foreignUuid('appointment_id')->comment('Foreign key to appointments table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('contact_id')->nullable()->comment('Foreign key to contacts table')->constrained()->nullOnDelete()->cascadeOnUpdate();

            $table->unsignedTinyInteger('invitee_type')->default(0)->comment('Invitee type');
            $table->unsignedTinyInteger('response')->default(0)->comment('Invitee response');

            $table->string('email')->comment('Invitee email');
            $table->string('first_name')->nullable()->comment('Invitee email');
            $table->string('last_name')->nullable()->comment('Invitee email');

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

        Schema::dropIfExists('appointment_contact_invitees');

        Schema::enableForeignKeyConstraints();
    }
};
