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
        Schema::create('pipeliner_webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('pl_reference')->comment('Reference to Pipeliner entity');
            $table->uuid('signature')->nullable()->comment('Webhook signature');

            $table->string('url', 250)->comment('Webhook url');
            $table->json('events')->comment('Webhook events');
            $table->boolean('insecure_ssl')->comment('Whether to validate remote side ssl certificate when delivering notifications');
            $table->json('options')->nullable()->comment('Webhook options');

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

        Schema::dropIfExists('pipeliner_webhooks');

        Schema::enableForeignKeyConstraints();
    }
};
