<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoWatcher;

/**
 * @extends Factory<TodoWatcher>
 *
 * (todo_id, user_id) is unique — a user watches a To-Do once. The default watcher
 * is a fresh user rather than the To-Do's creator, because a creator who watches
 * their own To-Do is a no-op that would hide a bug in the watcher list.
 */
class TodoWatcherFactory extends Factory
{
    protected $model = TodoWatcher::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'todo_id' => Todo::factory(),
            'user_id' => User::factory(),
        ];
    }
}
