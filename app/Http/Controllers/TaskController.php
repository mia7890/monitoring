<?php

namespace App\Http\Controllers;

use App\Models\FaeUser;
use App\Models\Task;
use App\Models\TaskUpdate;
use App\Services\MonitoringAuth;
use App\Services\UploadService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = MonitoringAuth::isAdmin();
        $currentFaeId = MonitoringAuth::faeId();

        // Query FAE list with summary statistics
        $faeQuery = FaeUser::with(['tasks']);
        if (!$isAdmin && $currentFaeId) {
            $faeQuery->where('id', $currentFaeId);
        }
        $rawFaeList = $faeQuery->orderBy('name', 'asc')->get();

        $faeList = $rawFaeList->map(function ($fae) {
            $tasks = $fae->tasks;
            $totalAssigned = $tasks->count();
            $completedCount = $tasks->where('status', 'Completed')->count();
            $inProgressCount = $tasks->where('status', 'In Progress')->count();
            $pendingCount = $tasks->where('status', 'Pending')->count();
            $overdueCount = $tasks->filter(function ($t) {
                return $t->isOverdue();
            })->count();
            $avgProgress = $totalAssigned > 0 ? (float)$tasks->avg('progress') : 0;

            $fae->total_assigned = $totalAssigned;
            $fae->completed_count = $completedCount;
            $fae->in_progress_count = $inProgressCount;
            $fae->pending_count = $pendingCount;
            $fae->overdue_count = $overdueCount;
            $fae->avg_progress = $avgProgress;
            return $fae;
        });

        // Query Tasks
        $tasksQuery = Task::with(['fae', 'updates'])
            ->withCount('updates');

        if (!$isAdmin && $currentFaeId) {
            $tasksQuery->where('fae_id', $currentFaeId);
        }

        $tasksList = $tasksQuery->orderBy('id', 'desc')->get();

        // Calculate global stats
        $totalFAE = $faeList->count();
        $totalTasks = $tasksList->count();
        $completedTasks = 0;
        $inProgressTasks = 0;
        $pendingTasks = 0;
        $overdueTasks = 0;

        foreach ($tasksList as $t) {
            if ($t->status === 'Completed') {
                $completedTasks++;
            } elseif ($t->status === 'In Progress') {
                $inProgressTasks++;
            } elseif ($t->status === 'Pending') {
                $pendingTasks++;
            }

            if ($t->isOverdue()) {
                $overdueTasks++;
            }
        }

        $allFaeForDropdown = FaeUser::orderBy('name', 'asc')->get();
        $preselectedFaeId = $request->query('assign_fae');

        return view('tasks.index', compact(
            'isAdmin',
            'currentFaeId',
            'faeList',
            'tasksList',
            'totalFAE',
            'totalTasks',
            'completedTasks',
            'inProgressTasks',
            'pendingTasks',
            'overdueTasks',
            'allFaeForDropdown',
            'preselectedFaeId'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'task_name' => 'required|string|max:255',
            'fae_id' => 'nullable|exists:fae_users,id',
            'priority' => 'nullable|in:Low,Medium,High,Urgent',
        ]);

        $task = Task::create([
            'fae_id' => $request->input('fae_id') ?: null,
            'region' => $request->input('region') ?: null,
            'course' => $request->input('course') ?: null,
            'task_name' => trim($request->input('task_name')),
            'description' => $request->input('description') ?: null,
            'deadline' => $request->input('deadline') ?: null,
            'status' => 'Pending',
            'progress' => 0,
            'priority' => $request->input('priority', 'Medium'),
        ]);

        return redirect()->route('tasks.index')->with('success', "Task '{$task->task_name}' created successfully!");
    }

    public function update(Request $request, Task $task)
    {
        $request->validate([
            'task_name' => 'required|string|max:255',
            'fae_id' => 'nullable|exists:fae_users,id',
            'priority' => 'nullable|in:Low,Medium,High,Urgent',
        ]);

        $task->update([
            'fae_id' => $request->input('fae_id') ?: null,
            'region' => $request->input('region') ?: null,
            'course' => $request->input('course') ?: null,
            'task_name' => trim($request->input('task_name')),
            'description' => $request->input('description') ?: null,
            'deadline' => $request->input('deadline') ?: null,
            'priority' => $request->input('priority', 'Medium'),
        ]);

        return redirect()->route('tasks.index')->with('success', "Task '{$task->task_name}' updated successfully!");
    }

    public function destroy(Task $task)
    {
        $taskName = $task->task_name;

        $attachments = TaskUpdate::where('task_id', $task->id)
            ->pluck('attachment')
            ->filter()
            ->unique();

        $task->delete();

        foreach ($attachments as $attachment) {
            UploadService::delete($attachment);
        }

        return redirect()->route('tasks.index')->with('success', "Task '{$taskName}' deleted successfully.");
    }

    public function updateProgress(Request $request, Task $task)
    {
        $isAdmin = MonitoringAuth::isAdmin();
        $currentFaeId = MonitoringAuth::faeId();

        if (!$isAdmin && $task->fae_id !== $currentFaeId) {
            return back()->with('error', 'Unauthorized: You can only update your own assigned tasks.');
        }

        $progress = max(0, min(100, (int)$request->input('progress', 0)));
        $status = $progress >= 100 ? 'Completed' : ($progress > 0 ? 'In Progress' : 'Pending');

        $task->update([
            'progress' => $progress,
            'status' => $status,
        ]);

        return back()->with('success', 'Task progress updated.');
    }

    public function storeUpdate(Request $request)
    {
        $request->validate([
            'task_id' => 'required|exists:tasks,id',
            'message' => 'required|string',
            'attachment' => 'nullable|file|max:10240', // 10MB
            'status' => ['nullable', Rule::in(['Pending', 'In Progress', 'Completed', 'Overdue'])],
            'progress' => 'nullable|integer|between:0,100',
        ]);

        $taskId = (int)$request->input('task_id');
        $isAdmin = MonitoringAuth::isAdmin();
        $currentFaeId = MonitoringAuth::faeId();

        $task = Task::findOrFail($taskId);
        if (!$isAdmin && $task->fae_id !== $currentFaeId) {
            return back()->with('error', 'Task not found or access denied.');
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $file = $request->file('attachment');
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, UploadService::ATTACHMENT_EXTENSIONS, true)) {
                return back()->with('error', 'Supported attachments are JPG, PNG, GIF, WEBP, BMP, PDF, DOC, DOCX, TXT, and ZIP.');
            }

            $attachmentPath = UploadService::storeFile($file, 'report_' . $taskId . '_');
        }

        $newProgress = $request->filled('progress')
            ? max(0, min(100, (int)$request->input('progress')))
            : $task->progress;

        $newStatus = $request->filled('status')
            ? trim((string)$request->input('status'))
            : ($request->filled('progress')
                ? ($newProgress >= 100 ? 'Completed' : ($newProgress > 0 ? 'In Progress' : 'Pending'))
                : $task->status);

        TaskUpdate::create([
            'task_id' => $taskId,
            'fae_id' => $isAdmin ? null : $currentFaeId,
            'author_role' => $isAdmin ? 'admin' : 'fae',
            'author_name' => $isAdmin ? 'Administrator' : MonitoringAuth::faeName(),
            'message' => trim($request->input('message')),
            'attachment' => $attachmentPath,
            'progress_at_update' => $newProgress,
            'status_at_update' => $newStatus,
        ]);

        $task->update([
            'progress' => $newProgress,
            'status' => $newStatus,
        ]);

        $redirectRoute = $request->input('redirect_to', 'tasks.index');
        return redirect()->route($redirectRoute === 'dashboard' ? 'dashboard' : 'tasks.index')
            ->with('success', 'Work report update submitted successfully.');
    }

    public function getTimeline(Request $request)
    {
        $taskId = (int)$request->query('task_id', 0);
        $isAdmin = MonitoringAuth::isAdmin();
        $currentFaeId = MonitoringAuth::faeId();

        $task = Task::with('fae')->find($taskId);
        if (!$task || (!$isAdmin && $task->fae_id !== $currentFaeId)) {
            return response()->json(['success' => false, 'error' => 'Task not found or access denied.'], 404);
        }

        $updates = TaskUpdate::where('task_id', $taskId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'task' => [
                'id' => $task->id,
                'task_name' => $task->task_name,
                'region' => $task->region,
                'course' => $task->course,
                'description' => $task->description,
                'deadline' => $task->deadline ? $task->deadline->format('Y-m-d') : null,
                'status' => $task->status,
                'progress' => $task->progress,
                'priority' => $task->priority,
                'fae_name' => $task->fae ? $task->fae->name : null,
                'fae_code' => $task->fae ? $task->fae->fae_code : null,
            ],
            'updates' => $updates,
            'current_user_name' => $isAdmin ? 'Administrator' : MonitoringAuth::faeName(),
            'is_admin' => $isAdmin,
        ]);
    }
}
