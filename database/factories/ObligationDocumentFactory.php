<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationDocument;

/**
 * @extends Factory<ObligationDocument>
 *
 * The module document table that Phase 8 replaced with the shared `attachments`.
 * Kept because the dual write still populates it.
 */
class ObligationDocumentFactory extends Factory
{
    protected $model = ObligationDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word().'.'.fake()->randomElement(['pdf', 'docx', 'png']);

        return [
            'obligation_id' => Obligation::factory(),
            'document_type' => fake()->randomElement(['contract', 'invoice', 'certificate', 'renewal_notice']),
            'file_name' => $name,
            'file_path' => 'obligations/'.Str::random(20).'/'.$name,
            'file_size' => fake()->numberBetween(1024, 8_000_000),
            'mime_type' => fake()->randomElement(['application/pdf', 'image/png']),
            'document_date' => now()->toDateString(),
            'expiry_date' => null,
            'uploaded_by' => User::factory(),
        ];
    }
}
