<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('importable_column_aliases', static function (Blueprint $table): void {
            $table->index(['alias']);
        });
    }

    public function down(): void
    {
        Schema::table('importable_column_aliases', static function (Blueprint $table): void {
            $table->dropIndex(['alias']);
        });
    }
};
