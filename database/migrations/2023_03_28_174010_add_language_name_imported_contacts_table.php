<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('imported_contacts', function (Blueprint $table): void {
            $table->string('language_name')
                ->nullable()
                ->after('job_title')
                ->comment('The language name');
        });
    }

    public function down(): void
    {
        Schema::table('imported_contacts', function (Blueprint $table): void {
            $table->dropColumn('language_name');
        });
    }
};
