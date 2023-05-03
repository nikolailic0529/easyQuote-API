<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('roles', static function (Blueprint $table): void {
            $table->dropIndex(['activated_at']);

            $table->boolean('is_active')
                ->after('activated_at')
                ->invisible()
                ->virtualAs(new Expression('activated_at IS NOT NULL'));

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('roles', static function (Blueprint $table): void {
            $table->index('activated_at');

            $table->dropIndex(['is_active']);
            $table->dropColumn('is_active');
        });
    }
};
