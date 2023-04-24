<?php

namespace Database\Factories;

use App\Domain\Mail\Models\MailLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MailLogFactory extends Factory
{
    protected $model = MailLog::class;

    public function definition(): array
    {
        return [
            'message_id' => Str::random(100),
            'from' => [$this->faker->safeEmail() => null],
            'to' => [$this->faker->safeEmail() => null],
            'subject' => $this->faker->words(asText: true),
            'body' => $this->faker->text(),
            'sent_at' => now(),
        ];
    }
}
