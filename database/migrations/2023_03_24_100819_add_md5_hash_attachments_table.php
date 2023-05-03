<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('attachments', static function (Blueprint $table): void {
            $table->char('md5_hash', 36)
                ->nullable()
                ->after('filename')
                ->comment('MD5 hash of the attachment content');
        });
    }

    public function down(): void
    {
        Schema::table('attachments', static function (Blueprint $table): void {
            $table->dropColumn('md5_hash');
        });
    }
};
