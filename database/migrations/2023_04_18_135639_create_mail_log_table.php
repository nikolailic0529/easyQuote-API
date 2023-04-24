<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mail_log', static function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('message_id')->comment('Message Id');

            $table->json('cc')->nullable()->comment('CC Header');
            $table->json('bcc')->nullable()->comment('BCC Header');
            $table->json('reply_to')->nullable()->comment('ReplyTo Header');
            $table->json('from')->comment('From Header');
            $table->json('to')->comment('To Header');

            $table->string('subject', 250)->nullable()->comment('Message Subject');
            $table->text('body')->comment('Message Body');

            $table->dateTime('sent_at');

            $table->timestamps();
            $table->softDeletes()->index();

            $table->rawIndex(DB::raw('sent_at desc'), 'mail_log_sent_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('mail_log');

        Schema::enableForeignKeyConstraints();
    }
};
