<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * A shared-platform comment — DATABASE-ARCHITECTURE.md §4.5.
 *
 * `mentions` IS exposed, unlike a To-Do's `recurrence_rule`. The mention list is
 * itself the user-visible fact ("Alice was notified"), not an internal encoding,
 * and a client rendering a thread needs it to badge the right rows.
 */
class CommentResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'body' => (string) $this->body,
            'user_id' => (int) $this->user_id,
            'parent_id' => $this->parent_id,
            'mentions' => array_values($this->mentions ?? []),
            'edited_at' => $this->edited_at?->toIso8601String(),
            'author' => $this->whenLoaded(
                'author',
                fn (): ?array => $this->author === null
                    ? null
                    : ['id' => (int) $this->author->id, 'name' => (string) $this->author->name],
            ),
            // Nested rather than flat: a client rendering a thread needs the tree,
            // and one that does not can ignore the key. Returning a flat list would
            // force every client to rebuild the hierarchy from `parent_id`.
            'replies' => self::collection($this->whenLoaded('replies')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function schema(): array
    {
        return [
            'id' => ['type' => 'integer'],
            'body' => ['type' => 'string'],
            'user_id' => ['type' => 'integer'],
            'parent_id' => self::nullableInt(),
            'mentions' => ['type' => 'array', 'items' => ['type' => 'integer']],
            'edited_at' => self::dateTime(),
            'author' => ['type' => ['object', 'null'], 'ref' => PersonResource::class],
            'replies' => ['type' => 'array', 'items' => ['type' => 'object']],
            'created_at' => self::dateTime(),
            'updated_at' => self::dateTime(),
        ];
    }
}
