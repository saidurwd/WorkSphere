<?php

namespace Modules\Meetings\Services;

use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingTag;

/**
 * Dual-writes meeting tags onto the shared `tags` / `taggables` tables.
 *
 * GAP-048. Meetings had their own `meeting_tags` / `meeting_tag_map` pair, so a
 * tag applied to a meeting was invisible to a tag applied to a To-Do, and no
 * cross-module tag query existed.
 *
 * **Dual-write, not migration.** Meetings continue to write `meeting_tag_map`
 * unchanged while also writing the shared pair; the existing meeting read paths
 * keep working untouched. Phase 8 backfills the legacy rows and Phase 15 drops
 * the old tables. Replacing the write today would break every meeting screen that
 * still reads `meeting_tag_map`.
 */
class MeetingTagSynchroniser
{
    /**
     * Mirror a meeting's tag names onto the shared tables.
     *
     * @param  Collection<int, MeetingTag>|array<int, MeetingTag>  $legacyTags
     * @return list<int> The shared tag ids now attached.
     */
    public function sync(int $meetingId, $legacyTags, ?int $createdBy = null): array
    {
        $sharedIds = [];

        foreach ($legacyTags as $legacy) {
            $shared = Tag::findOrCreateByName($legacy->name, $legacy->color ?? null);

            DB::table('taggables')->insertOrIgnore([
                'tag_id' => $shared->id,
                'taggable_type' => Meeting::class,
                'taggable_id' => $meetingId,
                'created_by' => $createdBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sharedIds[] = $shared->id;
        }

        return $sharedIds;
    }

    /**
     * Mirror newly created legacy tags, without disturbing what is attached.
     */
    public function syncNames(int $meetingId, array $names, ?int $createdBy = null): array
    {
        $sharedIds = [];

        foreach ($names as $name) {
            if (trim((string) $name) === '') {
                continue;
            }

            $shared = Tag::findOrCreateByName(trim((string) $name));

            DB::table('taggables')->insertOrIgnore([
                'tag_id' => $shared->id,
                'taggable_type' => Meeting::class,
                'taggable_id' => $meetingId,
                'created_by' => $createdBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sharedIds[] = $shared->id;
        }

        return $sharedIds;
    }

    /**
     * The migration plan for Phase 8, stated rather than executed.
     *
     * @return array<string, string>
     */
    public static function migrationPlan(): array
    {
        return [
            'phase' => '8',
            'source' => 'meeting_tag_map -> taggables',
            'approach' => 'insert-or-ignore per legacy row, matching on the tag name; safe to re-run',
            'read_switch' => 'Point Meeting::tags() at the shared pair only after a row-count reconciliation',
            'drop' => 'Phase 15, once no read path touches meeting_tag_map',
            'rows' => 'INSERT INTO taggables (tag_id, taggable_type, taggable_id, created_by, created_at, updated_at)
                       SELECT t.id, Meeting::class, mtm.meeting_id, NULL, NOW(), NOW()
                       FROM meeting_tag_map mtm
                       JOIN meeting_tags mt ON mt.id = mtm.tag_id
                       JOIN tags t ON t.slug = Tag::slugify(mt.name)
                       ON DUPLICATE KEY UPDATE updated_at = updated_at',
        ];
    }
}
