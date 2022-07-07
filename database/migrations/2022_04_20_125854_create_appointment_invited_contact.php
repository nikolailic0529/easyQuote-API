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
        Schema::create('appointment_invited_contact', function (Blueprint $table) {
            $table->foreignUuid('appointment_id')->comment('Foreign key to appointments table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignUuid('contact_id')->comment('Foreign key to contacts table')->constrained()->cascadeOnDelete()->cascadeOnUpdate();

            $table->primary(['appointment_id', 'contact_id']);
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

        Schema::dropIfExists('appointment_invited_contact');

        Schema::enableForeignKeyConstraints();
    }
};
