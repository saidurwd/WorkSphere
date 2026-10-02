<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Todos\Models\Todo;

/**
 * @extends Factory<Attachment>
 *
 * The default disk is `local`, which is PRIVATE. A factory that defaulted to
 * `public` would make the private-disk assertion in the upload tests pass for the
 * wrong reason on the fixture it happens to build.
 *
 * The path is under a random directory so two attachments created in one test do
 * not collide on the same stored file.
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word().'.'.fake()->randomElement(['pdf', 'png', 'docx']);

        return [
            'attachable_type' => (new Todo)->getMorphClass(),
            'attachable_id' => Todo::factory(),
            'disk' => 'local',
            'path' => 'attachments/'.Str::random(20).'/'.$name,
            'original_name' => $name,
            'mime_type' => fake()->randomElement([
                'application/pdf',
                'image/png',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]),
            'size' => fake()->numberBetween(1024, 2_000_000),
            'checksum' => hash('sha256', Str::random(32)),
            'uploaded_by' => User::factory(),
        ];
    }
}
