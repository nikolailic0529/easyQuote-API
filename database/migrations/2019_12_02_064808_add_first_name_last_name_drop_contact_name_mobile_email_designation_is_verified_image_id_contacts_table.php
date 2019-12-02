<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFirstNameLastNameDropContactNameMobileEmailDesignationIsVerifiedImageIdContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('contact_name')->nullable()->change();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('contact_type')->nullable()->change();
            $table->string('mobile')->nullable();
            $table->string('job_title')->nullable();
            $table->uuid('image_id')->nullable();
            $table->foreign('image_id')->references('id')->on('images')->onDelete('set null');
            $table->boolean('is_verified')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('contact_type')->change();
            $table->dropForeign(['image_id']);
            $table->dropColumn([
                'first_name',
                'last_name',
                'mobile',
                'job_title',
                'image_id',
                'is_verified'
            ]);
        });
    }
}
