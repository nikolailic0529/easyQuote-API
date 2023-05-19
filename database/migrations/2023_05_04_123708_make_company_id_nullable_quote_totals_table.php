<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        DB::table('quote_totals')->delete();

        Schema::table('quote_totals', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('quote_totals', static function (Blueprint $table): void {
            $table->foreignUuid('company_id')
                ->nullable()
                ->after('customer_id')
                ->comment('Foreign key to companies table')
                ->constrained('companies')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        DB::table('quote_totals')->delete();

        Schema::table('quote_totals', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
        });

        Schema::table('quote_totals', static function (Blueprint $table): void {
            $table->foreignUuid('company_id')
                ->after('customer_id')
                ->comment('Foreign key to companies table')
                ->constrained('companies')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }
};
