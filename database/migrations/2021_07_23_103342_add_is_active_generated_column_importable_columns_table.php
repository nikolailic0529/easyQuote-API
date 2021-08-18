<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIsActiveGeneratedColumnImportableColumnsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importable_columns', function (Blueprint $table) {
            $table->dropIndex(['activated_at']);

            $table->boolean('is_active')->after('activated_at')->virtualAs(new Expression('activated_at IS NOT NULL'));

            $table->index('is_active');
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
            $table->index('activated_at');

            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
}
