<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Todos\Models\Todo;

/**
 * @extends Factory<Comment>
 *
 * Comments are polymorphic, so the default subject is a To-Do — the only
 * polymorphic parent with a factory. Tests attaching a comment to a Meeting or a
 * Task pass `for()` with the subject explicitly.
 *
 * `mentions` is left NULL rather than an empty array: NULL means "nobody was
 * mentioned", an empty array means "mentioned, resolved to nobody", and
 * `Comment::mentioning()` treats only the former as no mention at all.
 */
class CommentFactory extends Factory
{
    protected $model = Comment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'commentable_type' => (new Todo)->getMorphClass(),
            'commentable_id' => Todo::factory(),
            'user_id' => User::factory(),
            'parent_id' => null,
            'body' => fake()->paragraph(),
            'mentions' => null,
            'edited_at' => null,
        ];
    }

    /**
     * A reply to an existing comment.
     */
    public function replyTo(Comment $parent): static
    {
        return $this->state(fn (): array => [
            'commentable_type' => $parent->commentable_type,
            'commentable_id' => $parent->commentable_id,
            'parent_id' => $parent->id,
        ]);
    }
}
