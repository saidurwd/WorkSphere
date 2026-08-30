<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskTransfer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TaskTransferController extends Controller
{
    public function index(Request $request): View
    {
        $selectedTaskId = $request->integer('task_id', 0);

        $query = TaskTransfer::query()->with(['task', 'fromUser', 'toUser', 'transferredBy']);

        if ($selectedTaskId > 0) {
            $query->where('task_id', $selectedTaskId);
        }

        $transfers = $query->latest('transfer_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('task-transfers.index', [
            'transfers' => $transfers,
            'tasks' => Task::orderBy('title')->get(['id', 'title']),
            'users' => User::orderBy('name')->get(['id', 'name']),
            'selectedTaskId' => $selectedTaskId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'task_id' => ['required', 'exists:tasks,id'],
            'from_user_id' => ['nullable', 'exists:users,id'],
            'to_user_id' => ['required', 'exists:users,id'],
            'transferred_by' => ['nullable', 'exists:users,id'],
            'reason' => ['required', 'string', 'max:1000'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'file_title' => ['nullable', 'string', 'max:255'],
            'file_attache' => ['nullable', 'file', 'extensions:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip,jpg,jpeg,png,gif', 'max:10240'],
            'transfer_date' => ['nullable', 'date'],
        ]);

        $task = Task::findOrFail($validated['task_id']);

        $validated['from_user_id'] = $validated['from_user_id'] ?? $task->responsible_user_id;
        $validated['transferred_by'] = $validated['transferred_by'] ?? $request->user()->id;

        if ($request->hasFile('file_attache')) {
            $disk = config('tyro-dashboard.uploads.disk', 'public');
            $directory = config('tyro-dashboard.uploads.directory', 'uploads');
            $validated['file_attache'] = $request->file('file_attache')->store($directory, $disk);
        }

        TaskTransfer::create($validated);

        // Enhance the task system: reassign the task to the new responsible user.
        $task->update(['responsible_user_id' => $validated['to_user_id']]);

        $redirect = $validated['task_id'] ? ['task_id' => $validated['task_id']] : [];

        return redirect()->route('task-transfers.index', $redirect)
            ->with('success', 'Task transferred successfully.');
    }

    public function destroy(TaskTransfer $taskTransfer): RedirectResponse
    {
        $disk = config('tyro-dashboard.uploads.disk', 'public');

        if ($taskTransfer->file_attache && Storage::disk($disk)->exists($taskTransfer->file_attache)) {
            Storage::disk($disk)->delete($taskTransfer->file_attache);
        }

        $taskId = $taskTransfer->task_id;

        $taskTransfer->delete();

        $redirect = $taskId ? ['task_id' => $taskId] : [];

        return redirect()->route('task-transfers.index', $redirect)
            ->with('success', 'Transfer deleted successfully.');
    }
}
