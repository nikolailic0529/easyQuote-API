<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportedRowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('imported_rows', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');

            $table->uuid('quote_file_id');
            $table->foreign('quote_file_id')->references('id')->on('quote_files')->onUpdate('cascade')->onDelete('cascade');

            $table->json('columns_data')->nullable()->comment('Columns data');

            $table->string('group_name')->nullable()->comment('Belonging to the specific group');
            $table->boolean('is_selected')->default(false)->comment('Determine whether row is selected');
            $table->boolean('is_one_pay')->default(false)->comment('Determine whether row is one off pay');

            $table->unsignedSmallInteger('page')->nullable();

            $table->timestamps();
            $table->timestamp('processed_at')->nullable();
            $table->softDeletes()->index();

            $table->index(['group_name', 'deleted_at']);
            $table->index(['is_selected', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('imported_rows');
    }
}
