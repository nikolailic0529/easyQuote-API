<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('opportunity_form_schemas', function (Blueprint $table): void {
            $table->json('sidebar_0')->default(DB::raw('(JSON_ARRAY())'))
                ->after('form_data')
                ->comment('Schema of sidebar 0');
        });
    }

    public function down(): void
    {
        Schema::table('opportunity_form_schemas', function (Blueprint $table): void {
            $table->dropColumn('sidebar_0');
        });
    }
};
