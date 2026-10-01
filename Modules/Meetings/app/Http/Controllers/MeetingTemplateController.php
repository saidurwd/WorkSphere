<?php

namespace Modules\Meetings\Http\Controllers;

use App\Enums\Priority;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingTemplate;
use Modules\Meetings\Models\MeetingTemplateAgenda;
use Modules\Meetings\Models\MeetingType;
use Modules\Meetings\Services\MeetingNumberService;

/**
 * Meeting templates — GAP-028.
 *
 * `meeting_templates` and `meeting_template_agendas` existed with models and
 * relations and were **entirely unwired**: no route created one, and nothing
 * could use one. This is the CRUD plus the operation the table was modelled for,
 * "schedule a meeting from this template".
 *
 * Scheduling copies the template's agenda into a real meeting and records
 * `template_id` on it, so the meeting stays traceable to its source. Without that
 * column a template-built meeting is indistinguishable from a hand-typed one and
 * a later template change cannot be reasoned about.
 */
class MeetingTemplateController extends Controller
{
    public function __construct(
        private readonly MeetingNumberService $numbers,
        private readonly ActivityLogger $activity,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('meeting.manage_templates');

        $templates = MeetingTemplate::query()
            ->with('meetingType')
            ->withCount('agendaItems')
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.trim((string) $request->input('search')).'%'))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('meetings.templates.index', [
            'templates' => $templates,
            'filters' => ['search' => $request->input('search')],
        ]);
    }

    public function create(): View
    {
        $this->authorize('meeting.manage_templates');

        return view('meetings.templates.create', [
            'template' => new MeetingTemplate(['is_active' => true, 'default_priority' => Priority::Normal->value]),
            'types' => $this->types(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('meeting.manage_templates');

        $template = DB::transaction(function () use ($request): MeetingTemplate {
            $validated = $request->validate($this->rules());

            $template = MeetingTemplate::query()->create([
                // `agenda` / `agenda_description` are parallel input arrays, not
                // columns; they are consumed separately below.
                ...collect($validated)->except(['agenda', 'agenda_description'])->all(),
                'created_by' => $request->user()->id,
            ]);

            // Agenda items arrive as parallel arrays; zip them so item N pairs with
            // title N rather than relying on array order elsewhere.
            foreach ($request->input('agenda', []) as $index => $title) {
                if (trim((string) $title) === '') {
                    continue;
                }

                MeetingTemplateAgenda::query()->create([
                    'template_id' => $template->id,
                    'title' => trim((string) $title),
                    'description' => $request->input("agenda_description.{$index}"),
                    'sort_order' => $index,
                ]);
            }

            return $template;
        });

        $this->activity->record(
            MeetingTemplate::class,
            $template,
            'created',
            null,
            ['name' => $template->name],
            $request->user()->id,
        );

        return redirect()->route('meetings.templates.show', $template)
            ->with('success', 'Template created.');
    }

    public function show(MeetingTemplate $template): View
    {
        $this->authorize('meeting.manage_templates');

        return view('meetings.templates.show', [
            'template' => $template->load(['meetingType', 'agendaItems']),
            'types' => $this->types(),
        ]);
    }

    public function edit(MeetingTemplate $template): View
    {
        $this->authorize('meeting.manage_templates');

        return view('meetings.templates.edit', [
            'template' => $template->load('agendaItems'),
            'types' => $this->types(),
        ]);
    }

    public function update(Request $request, MeetingTemplate $template): RedirectResponse
    {
        $this->authorize('meeting.manage_templates');

        $validated = $request->validate($this->rules());

        $template->update([
            ...collect($validated)->except(['agenda', 'agenda_description'])->all(),
            'updated_by' => $request->user()->id,
        ]);

        $this->activity->record(
            MeetingTemplate::class,
            $template,
            'updated',
            ['name' => $template->getRawOriginal('name')],
            ['name' => $template->name],
            $request->user()->id,
        );

        return redirect()->route('meetings.templates.show', $template)
            ->with('success', 'Template updated.');
    }

    public function destroy(Request $request, MeetingTemplate $template): RedirectResponse
    {
        $this->authorize('meeting.manage_templates');

        $template->delete();

        return redirect()->route('meetings.templates.index')
            ->with('success', 'Template deleted.');
    }

    /**
     * Schedule a real meeting from a template.
     *
     * The whole operation is one transaction: a meeting with a copied agenda and
     * no `template_id` would be indistinguishable from a hand-typed one, and a
     * rolled-back half would leave an agenda pointing at a meeting that does not
     * exist.
     */
    public function schedule(Request $request, MeetingTemplate $template): RedirectResponse
    {
        $this->authorize('meeting.manage_templates');

        if (! $template->is_active) {
            return back()->with('error', 'That template is inactive and cannot be scheduled.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'meeting_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'department_id' => ['nullable', Rule::exists('departments', 'id')],
            'chairperson_id' => ['nullable', Rule::exists('users', 'id')],
            'organizer_id' => ['nullable', Rule::exists('users', 'id')],
            'location_id' => ['nullable', Rule::exists('locations', 'id')],
        ]);

        // `meetings.meeting_no` is NOT NULL, so the number has to exist before the
        // insert. MeetingNumberService only reads `meeting_date`, so an unsaved
        // instance carrying the validated date produces the right number without
        // needing a row first.
        $draft = new Meeting(['meeting_date' => $validated['meeting_date']]);

        $meeting = DB::transaction(function () use ($request, $template, $validated, $draft): Meeting {
            $meeting = Meeting::query()->create([
                'title' => $validated['title'],
                'meeting_type_id' => $template->meeting_type_id,
                'organizer_id' => $validated['organizer_id'] ?? $request->user()->id,
                'chairperson_id' => $validated['chairperson_id'] ?? $validated['organizer_id'] ?? $request->user()->id,
                'department_id' => $validated['department_id'] ?? $request->user()?->employee?->department_id,
                // The template's default location text is copied; an explicitly
                // chosen location row wins when given.
                'location' => $template->default_location,
                'location_id' => $validated['location_id'] ?? null,
                'meeting_date' => $validated['meeting_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'priority' => $template->default_priority,
                'status' => 'scheduled',
                'template_id' => $template->id,
                'meeting_no' => $this->numbers->generate($draft),
                'created_by' => $request->user()->id,
            ]);

            foreach ($template->agendaItems as $item) {
                $meeting->agendas()->create([
                    'agenda_no' => $item->sort_order + 1,
                    'title' => $item->title,
                    'description' => $item->description,
                    'estimated_minutes' => $template->default_duration,
                    'status' => 'pending',
                    'sort_order' => $item->sort_order,
                ]);
            }

            return $meeting;
        });

        $this->activity->record(
            MeetingTemplate::class,
            $template,
            'scheduled',
            null,
            ['meeting_id' => $meeting->id],
            $request->user()->id,
        );

        return redirect()->route('meetings.show', $meeting)
            ->with('success', 'Meeting scheduled from the template.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'meeting_type_id' => ['required', Rule::exists('meeting_types', 'id')],
            'description' => ['nullable', 'string', 'max:2000'],
            'default_duration' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'default_location' => ['nullable', 'string', 'max:255'],
            'default_priority' => ['required', Rule::enum(Priority::class)],
            'is_active' => ['nullable', 'boolean'],
            'agenda' => ['nullable', 'array'],
            'agenda.*' => ['nullable', 'string', 'max:255'],
            'agenda_description.*' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return Collection<int, MeetingType>
     */
    protected function types()
    {
        return MeetingType::query()->orderBy('name')->get(['id', 'name']);
    }
}
