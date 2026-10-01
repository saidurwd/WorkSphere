<?php

namespace Modules\Projects\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Projects\Models\Project;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Project::query()
            ->withCount('tasks')
            ->latest('created_at');

        if (! $user->hasPermission('project.view')) {
            $query->where('user_id', $user->id);
        }

        $filters = [
            'search' => $request->string('search')->trim()->toString(),
        ];

        $query->when($filters['search'] !== '', function ($q) use ($filters) {
            $search = "%{$filters['search']}%";
            $q->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search);
            });
        });

        $projects = $query->paginate(15)->withQueryString();

        return view('projects.index', [
            'projects' => $projects,
            'filters' => $filters,
        ]);
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);

        $project->load(['user']);
        $tasks = $project->tasks()
            ->with(['responsibleUser'])
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('projects.show', [
            'project' => $project,
            'tasks' => $tasks,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Project::class);

        return view('projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Auth::user()->projects()->create($validated);

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function edit(Project $project): View
    {
        $this->authorize('update', $project);

        return view('projects.edit', [
            'project' => $project,
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $project->update($validated);

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }
}
