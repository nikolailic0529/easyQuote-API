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
        Schema::create('pipeliner_webhook_events', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('webhook_id')->comment('Foreign key to pipeliner_webhooks table')
                ->constrained('pipeliner_webhooks')->cascadeOnDelete()->cascadeOnUpdate();

            $table->string('event')->comment('Notification event');
            $table->dateTime('event_time')->comment('Notification event time');

            $table->json('payload')->comment('Notification payload');

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

        Schema::dropIfExists('pipeliner_webhook_events');

        Schema::enableForeignKeyConstraints();
    }
};
