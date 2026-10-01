<x-form.input name="name" label="Template name" :value="$template->name" required maxlength="255" />

<x-form.textarea name="description" label="Description" :value="$template->description" rows="3" maxlength="2000" />

<div class="row g-3">
    <div class="col-md-6">
        <x-form.select name="meeting_type_id" label="Meeting type" :value="$template->meeting_type_id" required>
            <option value="">Choose a type…</option>
            @foreach ($types as $type)
                <option value="{{ $type->id }}" @selected((string) old('meeting_type_id', $template->meeting_type_id) === (string) $type->id)>
                    {{ $type->name }}
                </option>
            @endforeach
        </x-form.select>
    </div>

    <div class="col-md-6">
        <x-form.select name="default_priority" label="Default priority" :value="$template->default_priority">
            @foreach (\App\Enums\Priority::cases() as $priority)
                <option value="{{ $priority->value }}" @selected(old('default_priority', $template->default_priority) === $priority->value)>
                    {{ $priority->label() }}
                </option>
            @endforeach
        </x-form.select>
    </div>

    <div class="col-md-6">
        <x-form.input name="default_duration" label="Default duration (minutes)" type="number" min="1" max="1440"
                      :value="$template->default_duration" />
    </div>

    <div class="col-md-6">
        <x-form.input name="default_location" label="Default location" :value="$template->default_location"
                      maxlength="255" />
    </div>
</div>

<div class="form-check form-switch mb-3">
    <input class="form-check-input" type="checkbox" name="is_active" id="template-active" value="1"
           @checked(old('is_active', $template->is_active ?? true))>
    <label class="form-check-label" for="template-active">Available to schedule from</label>
</div>

<fieldset>
    <legend class="form-label">Standing agenda</legend>

    @php $existing = $template->relationLoaded('agendaItems') ? $template->agendaItems : collect(); @endphp

    @for ($i = 0; $i < max(3, $existing->count() + 1); $i++)
        @php $item = $existing->get($i); @endphp
        <div class="row g-2 mb-2">
            <div class="col-md-6">
                <label for="agenda-{{ $i }}" class="visually-hidden">Agenda item {{ $i + 1 }} title</label>
                <input id="agenda-{{ $i }}" type="text" name="agenda[{{ $i }}]" class="form-control"
                       placeholder="Item {{ $i + 1 }}" maxlength="255"
                       value="{{ old("agenda.$i", $item->title ?? '') }}">
            </div>
            <div class="col-md-6">
                <label for="agenda-description-{{ $i }}" class="visually-hidden">Agenda item {{ $i + 1 }} description</label>
                <input id="agenda-description-{{ $i }}" type="text" name="agenda_description[{{ $i }}]"
                       class="form-control" placeholder="Description (optional)" maxlength="2000"
                       value="{{ old("agenda_description.$i", $item->description ?? '') }}">
            </div>
        </div>
    @endfor
</fieldset>
