<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('contacts', static function (Blueprint $table): void {
            $table->foreignUuid('language_id')
                ->nullable()
                ->after('sales_unit_id')
                ->comment('Foreign key to languages table')
                ->constrained('languages')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('contacts', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('language_id');
        });

        Schema::enableForeignKeyConstraints();
    }
};
