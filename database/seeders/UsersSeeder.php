<?php

namespace Database\Seeders;

use App\Domain\Authorization\Models\{Role};
use App\Domain\Timezone\Models\Timezone;
use App\Domain\User\Models\User;
use Faker\Generator as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeders.
     *
     * @return void
     */
    public function run(Faker $faker)
    {
        if (app()->environment('production')) {
            return;
        }

        // Empty the users table
        Schema::disableForeignKeyConstraints();

        DB::table('users')->delete();

        Schema::enableForeignKeyConstraints();

        $users = json_decode(file_get_contents(__DIR__.'/models/users.json'), true);
        $administratorRole = Role::findByName('Administrator');

        collect($users)->each(function ($user) use ($administratorRole, $faker) {
            $timezoneId = Timezone::query()->orderByRaw('RAND()')->value('id');

            $user = User::create([
                'email' => $user['email'],
                'first_name' => $faker->firstName,
                'middle_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'password' => isset($user['password']) ? bcrypt($user['password']) : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'timezone_id' => $timezoneId,
            ]);

            $user->assignRole($administratorRole);
        });
    }
}
