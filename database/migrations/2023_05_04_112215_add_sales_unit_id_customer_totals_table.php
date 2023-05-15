<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('customer_totals', static function (Blueprint $table): void {
            $table->foreignUuid('sales_unit_id')
                ->nullable()
                ->after('id')
                ->comment('Foreign key to sales_units table')
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_totals', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('sales_unit_id');
        });
    }
};
