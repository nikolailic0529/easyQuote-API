<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    public function up(): void
    {
        DB::table('roles')
            ->where('guard_name', '<>', 'api')
            ->update(['guard_name' => 'api']);
    }

    public function down(): void
    {
    }
};
