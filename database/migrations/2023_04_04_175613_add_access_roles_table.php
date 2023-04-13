<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('roles', static function (Blueprint $table): void {
            $table->json('access')
                ->default(DB::raw('(JSON_OBJECT())'))
                ->after('is_system')
                ->comment('Role access meta');
        });
    }

    public function down(): void
    {
        Schema::table('roles', static function (Blueprint $table): void {
            $table->dropColumn('access');
        });
    }
};
