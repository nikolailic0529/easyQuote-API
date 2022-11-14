<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'first_name' => Str::filterLetters($this->faker->firstName()),
            'last_name' => Str::filterLetters($this->faker->lastName()),
            'email' => Str::random(100).'@example.com',
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'timezone_id' => DB::table('timezones')->value('id'),
            'remember_token' => Str::random(10),
            'password_changed_at' => now(),
            // 'last_activity_at'    => now(),
            'ip_address' => $this->faker->ipv4,
            'already_logged_in' => 1,
        ];
    }

    public function configure(): self
    {
        return $this->afterCreating(static function (User $user): void {
            $user->syncRoles(R_SUPER);
        });
    }
}
