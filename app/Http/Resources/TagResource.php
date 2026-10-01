<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * A tag on the shared `taggables` pivot.
 *
 * Colour is included because a tag without it renders as an indistinguishable
 * grey chip; the palette itself lives in the client's stylesheet.
 */
class TagResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'slug' => (string) $this->slug,
            'color' => $this->color,
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
            'slug' => ['type' => 'string'],
            'color' => self::nullableString(),
        ];
    }
}
