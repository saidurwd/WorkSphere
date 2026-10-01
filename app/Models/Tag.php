<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Todos\Models\Todo;

/**
 * Shared tag vocabulary — DATABASE-ARCHITECTURE.md §4.7.
 *
 * A tag is a label with a name and a slug; which records carry it is decided by
 * `taggables`. The existing `meeting_tags` / `meeting_tag_map` pair is
 * dual-written onto these in Phase 7 and migrated in Phase 8.
 */
class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
    ];

    public function todos(): BelongsToMany
    {
        return $this->belongsToMany(Todo::class, 'taggables')
            ->withPivot('created_by')
            ->withTimestamps();
    }

    /**
     * Find or create by name, deriving the slug. Names are unique, so this is
     * the only correct way to attach an existing tag from a form post.
     */
    public static function findOrCreateByName(string $name, ?string $color = null): self
    {
        $slug = static::slugify($name);

        return static::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $name, 'color' => $color],
        );
    }

    public static function slugify(string $value): string
    {
        $slug = strtolower(trim($value));

        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

        return trim($slug, '-');
    }
}
