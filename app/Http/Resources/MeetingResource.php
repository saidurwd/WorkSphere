<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/**
 * A Meeting.
 *
 * `location` is exposed as the free-text field the schema already carries and
 * `location_id` alongside it, because both exist today and the API must not
 * quietly pick a winner: the structured reference was added in Phase 8 and rows
 * created before it still only have the text.
 */
class MeetingResource extends ApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'meeting_no' => $this->meeting_no,
            'title' => (string) $this->title,
            'description' => $this->description,
            'agenda' => $this->agenda,
            'status' => $this->status,
            'priority' => $this->priority,
            'meeting_type_id' => $this->meeting_type_id,
            'meeting_type' => $this->whenLoaded(
                'type',
                fn (): ?array => $this->type === null
                    ? null
                    : ['id' => (int) $this->type->id, 'name' => (string) $this->type->name],
            ),
            'organizer_id' => $this->organizer_id,
            'chairperson_id' => $this->chairperson_id,
            'organizer' => $this->whenLoaded(
                'organizer',
                fn (): ?array => $this->person($this->organizer),
            ),
            'department_id' => $this->department_id,
            'location' => $this->location,
            'location_id' => $this->location_id,
            'meeting_date' => $this->meeting_date?->toDateString(),
            'start_time' => $this->start_time?->format('H:i'),
            'end_time' => $this->end_time?->format('H:i'),
            'timezone' => $this->timezone,
            'minutes_status' => $this->minutes_status,
            'template_id' => $this->template_id,
            'counts' => [
                'participants' => (int) ($this->participants_count ?? $this->resource->participants()->count()),
                'agendas' => (int) ($this->agendas_count ?? $this->resource->agendas()->count()),
                'action_items' => (int) ($this->action_items_count ?? $this->resource->actionItems()->count()),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    protected function person(?object $user): ?array
    {
        return $user === null
            ? null
            : ['id' => (int) $user->id, 'name' => (string) $user->name];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function schema(): array
    {
        return [
            'id' => ['type' => 'integer'],
            'meeting_no' => self::nullableString(),
            'title' => ['type' => 'string'],
            'description' => self::nullableString(),
            'agenda' => self::nullableString(),
            'status' => self::nullableString(),
            'priority' => self::nullableString(),
            'meeting_type_id' => self::nullableInt(),
            'meeting_type' => ['type' => ['object', 'null']],
            'organizer_id' => self::nullableInt(),
            'chairperson_id' => self::nullableInt(),
            'organizer' => ['type' => ['object', 'null'], 'ref' => PersonResource::class],
            'department_id' => self::nullableInt(),
            'location' => self::nullableString(),
            'location_id' => self::nullableInt(),
            'meeting_date' => self::date(),
            'start_time' => self::nullableString(),
            'end_time' => self::nullableString(),
            'timezone' => self::nullableString(),
            'minutes_status' => self::nullableString(),
            'template_id' => self::nullableInt(),
            'counts' => ['type' => 'object'],
            'created_at' => self::dateTime(),
            'updated_at' => self::dateTime(),
        ];
    }
}
