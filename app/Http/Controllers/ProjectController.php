<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Project::query()
            ->withCount('tasks')
            ->latest('created_at');

        if (! $this->canManageAllProjects($user)) {
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
        $this->authorizeProject($project);

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
        return view('projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        Auth::user()->projects()->create($validated);

        return redirect()->route('projects.index')->with('success', 'Project created successfully.');
    }

    public function edit(Project $project): View
    {
        $this->authorizeProject($project);

        return view('projects.edit', [
            'project' => $project,
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $project->update($validated);

        return redirect()->route('projects.index')->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorizeProject($project);

        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Project deleted successfully.');
    }

    private function authorizeProject(Project $project): void
    {
        if ($this->canManageAllProjects(Auth::user())) {
            return;
        }

        if ($project->user_id !== Auth::id()) {
            abort(403);
        }
    }

    private function canManageAllProjects(mixed $user): bool
    {
        return method_exists($user, 'hasRole')
            && $user->hasRole('super-admin');
    }
}
