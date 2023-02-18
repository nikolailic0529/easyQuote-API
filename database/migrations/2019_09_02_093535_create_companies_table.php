<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->uuid('default_vendor_id')->nullable();
            $table->foreign('default_vendor_id')->references('id')->on('vendors')->onDelete('set null');

            $table->uuid('default_country_id')->nullable();
            $table->foreign('default_country_id')->references('id')->on('countries')->onDelete('set null');

            $table->string('name');
            $table->string('vat');
            $table->boolean('is_system')->default(false);

            $table->string('type');
            $table->string('category')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            $table->timestamps();
            $table->timestamp('activated_at')->index()->nullable();
            $table->softDeletes()->index();

            $table->unique(['vat', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
}
