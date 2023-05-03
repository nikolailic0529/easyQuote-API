<?php

namespace Database\Factories;

use App\Domain\Attachment\Enum\AttachmentType;
use App\Domain\Attachment\Models\Attachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        $ext = $this->faker->randomElement(['txt', 'csv', 'pdf', 'docx', 'xlsx']);
        $filename = implode('.', [Str::random(), $ext]);

        return [
            'type' => $this->faker->randomElement(AttachmentType::cases())->value,
            'filename' => $filename,
            'filepath' => implode(DIRECTORY_SEPARATOR, ['attachments', $filename]),
            'size' => mt_rand(1000, 2000),
            'extension' => $ext,
        ];
    }
}
