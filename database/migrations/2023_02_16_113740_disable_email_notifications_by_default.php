<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    public function up(): void
    {
        $settings = [
            'activities' => [
                'is_active' => [],
                'tasks' => [],
                'appointments' => [],
            ],
            'accounts_and_contacts' => [
                'is_active' => [],
                'ownership_change' => [],
            ],
            'opportunities' => [
                'is_active' => [],
                'ownership_change' => [],
            ],
            'quotes' => [
                'is_active' => [],
                'ownership_change' => [],
                'permissions_change' => [],
                'status_change' => [],
            ],
            'sync' => [
                'is_active' => [],
            ],
        ];

        $settings = collect($settings)
            ->map(static function (array $group): array {
                return collect($group)
                    ->map(static function (): array {
                        return [
                            'email_notif' => false,
                            'app_notif' => true,
                        ];
                    })
                    ->all();
            })
            ->all();

        DB::table('users')
            ->update([
                'notification_settings' => json_encode($settings),
            ]);
    }

    public function down(): void
    {
    }
};
