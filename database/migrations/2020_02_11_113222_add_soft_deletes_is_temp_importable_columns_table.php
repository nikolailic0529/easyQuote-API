<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddSoftDeletesIsTempImportableColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importable_columns', function (Blueprint $table) {
            $table->timestamps();
            $table->softDeletes();
            $table->boolean('is_temp')->default(false);
        });

        DB::transaction(function () {
            $now = now();
            DB::table('importable_columns')->update(['created_at' => $now, 'updated_at' => $now]);
            DB::table('importable_columns')->where('is_system', false)->update(['is_temp' => true]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('importable_columns', function (Blueprint $table) {
            $table->dropTimestamps();
            $table->dropSoftDeletes();
            $table->dropColumn('is_temp');
        });
    }
}
