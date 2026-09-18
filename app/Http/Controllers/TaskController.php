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

        // Query FAE list with summary statistics (Approved FAEs only)
        $faeQuery = FaeUser::approved()->with(['tasks']);
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

        $allFaeForDropdown = FaeUser::approved()->orderBy('name', 'asc')->get();
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
        $today = now()->startOfDay();

        $rawFae = $request->input('fae_id');
        if (!is_array($rawFae) && !is_null($rawFae) && $rawFae !== '') {
            $request->merge(['fae_id' => [$rawFae]]);
        }

        $request->validate([
            'task_name' => 'required|string|max:255',
            'fae_id' => 'required|array|min:1',
            'fae_id.*' => 'exists:fae_users,id',
            'deadline' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($today) {
                    if ($value && \Carbon\Carbon::parse($value)->startOfDay()->lt($today)) {
                        $fail('Task deadline cannot be in the past. Please choose today or a future date.');
                    }
                },
            ],
            'priority' => 'nullable|in:Low,Medium,High,Urgent',
        ], [
            'fae_id.required' => 'Please assign at least one FAE person to this task.',
            'fae_id.min' => 'Please select at least one FAE person.',
            'fae_id.*.exists' => 'One or more selected FAE members are invalid.',
        ]);

        $faeIds = (array) $request->input('fae_id');
        $taskName = trim($request->input('task_name'));
        $createdCount = 0;

        foreach ($faeIds as $faeId) {
            Task::create([
                'fae_id' => $faeId,
                'region' => $request->input('region') ?: null,
                'course' => $request->input('course') ?: null,
                'task_name' => $taskName,
                'description' => $request->input('description') ?: null,
                'deadline' => $request->input('deadline') ?: null,
                'status' => 'Pending',
                'progress' => 0,
                'priority' => $request->input('priority', 'Medium'),
            ]);
            $createdCount++;
        }

        $msg = $createdCount > 1 
            ? "{$createdCount} tasks created successfully and assigned to selected FAEs!"
            : "Task '{$taskName}' created successfully and assigned to FAE!";

        return redirect()->route('tasks.index')->with('success', $msg);
    }

    public function update(Request $request, Task $task)
    {
        $today = now()->startOfDay();

        $rawFae = $request->input('fae_id');
        if (is_array($rawFae)) {
            $rawFae = reset($rawFae);
            $request->merge(['fae_id' => $rawFae]);
        }

        $request->validate([
            'task_name' => 'required|string|max:255',
            'fae_id' => 'required|exists:fae_users,id',
            'deadline' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($today, $task) {
                    // Only enforce future deadline if deadline is being changed
                    if ($value && $task->deadline && $task->deadline->format('Y-m-d') !== $value) {
                        if (\Carbon\Carbon::parse($value)->startOfDay()->lt($today)) {
                            $fail('Task deadline cannot be in the past. Please choose today or a future date.');
                        }
                    }
                },
            ],
            'priority' => 'nullable|in:Low,Medium,High,Urgent',
        ], [
            'fae_id.required' => 'Please assign an FAE person to this task.',
            'fae_id.exists' => 'The selected FAE member does not exist.',
        ]);

        $oldFaeId = $task->fae_id;
        $newFaeId = (int)$request->input('fae_id');

        $task->update([
            'fae_id' => $newFaeId,
            'region' => $request->input('region') ?: null,
            'course' => $request->input('course') ?: null,
            'task_name' => trim($request->input('task_name')),
            'description' => $request->input('description') ?: null,
            'deadline' => $request->input('deadline') ?: null,
            'priority' => $request->input('priority', 'Medium'),
        ]);

        // If FAE was reassigned, log update remarks for both previous and newly assigned FAE
        if ($oldFaeId !== $newFaeId) {
            $oldFae = $oldFaeId ? FaeUser::find($oldFaeId) : null;
            $newFae = $newFaeId ? FaeUser::find($newFaeId) : null;
            $oldName = $oldFae ? $oldFae->name : 'Unassigned';
            $newName = $newFae ? $newFae->name : 'Unassigned';

            // 1. Notification for former FAE (if any)
            if ($oldFaeId) {
                TaskUpdate::create([
                    'task_id' => $task->id,
                    'fae_id' => $oldFaeId,
                    'author_role' => 'admin',
                    'author_name' => 'Administrator',
                    'message' => "Task '{$task->task_name}' was reassigned from you to {$newName}.",
                    'progress_at_update' => $task->progress,
                    'status_at_update' => $task->status,
                ]);
            }

            // 2. Notification for new FAE (if any)
            if ($newFaeId) {
                TaskUpdate::create([
                    'task_id' => $task->id,
                    'fae_id' => $newFaeId,
                    'author_role' => 'admin',
                    'author_name' => 'Administrator',
                    'message' => "Task '{$task->task_name}' was reassigned to you from {$oldName}.",
                    'progress_at_update' => $task->progress,
                    'status_at_update' => $task->status,
                ]);
            }
        }

        return redirect()->route('tasks.index')->with('success', "Task '{$task->task_name}' updated successfully!");
    }

    public function destroy(Task $task)
    {
        $taskName = $task->task_name;

        $updates = TaskUpdate::where('task_id', $task->id)->get();

        foreach ($updates as $up) {
            foreach ($up->attachments_list as $attachmentPath) {
                UploadService::delete($attachmentPath);
            }
        }

        $task->delete();

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
            'attachment' => 'nullable|file|max:10240', // legacy single
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|max:10240', // 10MB per file
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

        // Prevent non-admin updates if task is already 100% completed
        if (!$isAdmin && ($task->status === 'Completed' || (int)$task->progress >= 100)) {
            return back()->with('error', 'This task is 100% completed and locked. Please contact your administrator if you need to submit further updates.');
        }

        $storedPaths = [];

        // Handle multiple attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file && $file->isValid()) {
                    $ext = strtolower($file->getClientOriginalExtension());
                    if (!in_array($ext, UploadService::ATTACHMENT_EXTENSIONS, true)) {
                        return back()->with('error', "Invalid file type '.{$ext}'. Supported: JPG, PNG, GIF, WEBP, BMP, PDF, DOC, DOCX, TXT, ZIP.");
                    }
                    $storedPaths[] = UploadService::storeFile($file, 'report_' . $taskId . '_');
                }
            }
        }

        // Handle single legacy attachment if present
        if ($request->hasFile('attachment') && $request->file('attachment')->isValid()) {
            $file = $request->file('attachment');
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, UploadService::ATTACHMENT_EXTENSIONS, true)) {
                return back()->with('error', "Invalid file type '.{$ext}'. Supported: JPG, PNG, GIF, WEBP, BMP, PDF, DOC, DOCX, TXT, ZIP.");
            }
            $storedPaths[] = UploadService::storeFile($file, 'report_' . $taskId . '_');
        }

        $finalAttachment = null;
        if (count($storedPaths) === 1) {
            $finalAttachment = $storedPaths[0];
        } elseif (count($storedPaths) > 1) {
            $finalAttachment = json_encode(array_values($storedPaths));
        }

        $newProgress = $request->filled('progress')
            ? max(0, min(100, (int)$request->input('progress')))
            : $task->progress;

        $newStatus = $request->filled('status')
            ? trim((string)$request->input('status'))
            : ($request->filled('progress')
                ? ($newProgress >= 100 ? 'Completed' : ($newProgress > 0 ? 'In Progress' : 'Pending'))
                : $task->status);

        // If status is marked Completed, force progress to 100
        if ($newStatus === 'Completed' && $newProgress < 100) {
            $newProgress = 100;
        }

        TaskUpdate::create([
            'task_id' => $taskId,
            'fae_id' => $isAdmin ? null : $currentFaeId,
            'author_role' => $isAdmin ? 'admin' : 'fae',
            'author_name' => $isAdmin ? 'Administrator' : MonitoringAuth::faeName(),
            'message' => trim($request->input('message')),
            'attachment' => $finalAttachment,
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

    public function report(Request $request, Task $task)
    {
        $isAdmin = MonitoringAuth::isAdmin();
        $currentFaeId = MonitoringAuth::faeId();

        if (!$isAdmin && $task->fae_id !== $currentFaeId) {
            abort(403, 'Unauthorized: You can only view reports for your assigned tasks.');
        }

        $task->load(['fae.department', 'updates' => function ($q) {
            $q->orderBy('created_at', 'desc');
        }]);

        return view('tasks.report', compact('task', 'isAdmin'));
    }

    public function exportSummaryReport(Request $request)
    {
        $isAdmin = MonitoringAuth::isAdmin();
        $currentFaeId = MonitoringAuth::faeId();

        $query = Task::with(['fae.department', 'updates']);
        if (!$isAdmin && $currentFaeId) {
            $query->where('fae_id', $currentFaeId);
        }

        $tasksList = $query->orderBy('id', 'desc')->get();

        $totalTasks = $tasksList->count();
        $completedTasks = $tasksList->where('status', 'Completed')->count();
        $inProgressTasks = $tasksList->where('status', 'In Progress')->count();
        $pendingTasks = $tasksList->where('status', 'Pending')->count();
        $overdueTasks = $tasksList->filter(fn($t) => $t->isOverdue())->count();

        return view('tasks.summary_report', compact(
            'tasksList',
            'isAdmin',
            'totalTasks',
            'completedTasks',
            'inProgressTasks',
            'pendingTasks',
            'overdueTasks'
        ));
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
