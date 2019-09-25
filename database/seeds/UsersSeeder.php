<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Generator as Faker;
use App\Models\Role;
use App\Models\Data\Country;
use App\Models\Data\Timezone;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Faker $faker)
    {
        //Empty the users table
        Schema::disableForeignKeyConstraints();

        DB::table('users')->delete();

        Schema::enableForeignKeyConstraints();

        $users = json_decode(file_get_contents(__DIR__ . '/models/users.json'), true);

        collect($users)->each(function ($user) use ($faker) {

            $countryId = Country::orderByRaw("RAND()")->first()->id;
            $timezoneId = Timezone::orderByRaw("RAND()")->first()->id;

            DB::table('users')->insert([
                'id' => (string) Uuid::generate(4),
                'email' => $user['email'],
                'first_name' => $faker->firstName,
                'middle_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'password' => isset($user['password']) ? bcrypt($user['password']) : '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'country_id' => $countryId,
                'timezone_id' => $timezoneId
            ]);
        });
    }
}
