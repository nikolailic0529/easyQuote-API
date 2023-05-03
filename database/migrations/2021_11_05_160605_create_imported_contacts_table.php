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
        Schema::create('imported_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('contact_type')->nullable()->comment('Contact Type');

            $table->string('first_name')->nullable()->comment('Contact First Name');
            $table->string('last_name')->nullable()->comment('Contact Last Name');
            $table->string('email')->nullable()->comment('Contact Email');
            $table->string('phone')->nullable()->comment('Contact Phone No');
            $table->string('job_title')->nullable()->comment('Contact Job Title');
            $table->boolean('is_verified')->default(0)->comment('Whether The Contact Is Verified');

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

        Schema::dropIfExists('imported_contacts');

        Schema::enableForeignKeyConstraints();
    }
};
