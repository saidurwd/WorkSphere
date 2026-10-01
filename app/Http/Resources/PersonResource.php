<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * A referenced person.
 *
 * Two fields, and deliberately not more. Email is withheld: an API that renders
 * To-Dos must not become a staff directory, and the callers that legitimately
 * need an address have a user endpoint of their own to be authorised against.
 */
class PersonResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function schema(): array
    {
        return [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
        ];
    }
}
