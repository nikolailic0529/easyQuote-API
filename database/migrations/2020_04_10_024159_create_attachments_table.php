<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('type')->index()->comment('Attachment type');
            $table->string('filepath')->comment('Stored filepath');
            $table->string('filename')->comment('Original filename');
            $table->string('extension')->comment('File extension');
            $table->unsignedBigInteger('size')->default(0)->comment('File size in bytes');

            $table->timestamps();
            $table->softDeletes()->index();
        });

        Schema::create('attachables', function (Blueprint $table) {
            $table->uuid('attachment_id');
            $table->foreign('attachment_id')->references('id')->on('attachments')->onDelete('cascade');

            $table->uuidMorphs('attachable');

            $table->primary(['attachment_id', 'attachable_id', 'attachable_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('attachables');
    }
}
