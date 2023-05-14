<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('worldwide_quote_versions', static function (Blueprint $table): void {
            $table->foreignUuid('submitted_pa_invoice_address_id')
                ->nullable()
                ->after('sn_discount_id')
                ->comment('Foreign key to addresses table')
                ->constrained('addresses')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('worldwide_quote_versions', static function (Blueprint $table): void {
            $table->dropConstrainedForeignId('submitted_pa_invoice_address_id');
        });
    }
};
