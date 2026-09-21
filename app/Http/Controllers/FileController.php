<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskUpdate;
use App\Services\MonitoringAuth;
use App\Services\UploadService;
use Illuminate\Http\Request;

class FileController extends Controller
{
    /**
     * Stream a stored file to an authenticated monitoring user.
     */
    public function show(Request $request, string $path)
    {
        $resolvedPath = UploadService::resolve($path);

        if (!$resolvedPath || !$this->userCanAccess($path)) {
            abort(404);
        }

        return response()->file($resolvedPath);
    }

    /**
     * Enforce access control:
     *  - Administrators may access every stored file.
     *  - Profile / avatar images are shared within the workspace.
     *  - Task attachments and report attachments are restricted to the FAE assigned to the
     *    owning task (or an administrator).
     */
    private function userCanAccess(string $path): bool
    {
        if (MonitoringAuth::isAdmin()) {
            return true;
        }

        $currentFaeId = MonitoringAuth::faeId();
        if (!$currentFaeId) {
            return false;
        }

        $basename = basename(str_replace('\\', '/', $path));
        if (str_starts_with($basename, 'fae_') || str_starts_with($basename, 'admin_')) {
            return true;
        }

        // Check if file is attached directly to a Task assigned to the current FAE
        $task = Task::query()
            ->where('fae_id', $currentFaeId)
            ->where(function ($q) use ($path) {
                $q->where('attachment', $path)
                  ->orWhere('attachment', 'LIKE', '%' . $path . '%');
            })
            ->first();

        if ($task) {
            return true;
        }

        // Check if file is attached to a TaskUpdate on a task assigned to current FAE
        $update = TaskUpdate::query()
            ->where(function ($q) use ($path) {
                $q->where('attachment', $path)
                  ->orWhere('attachment', 'LIKE', '%' . $path . '%');
            })
            ->first();

        if (!$update || !$update->task) {
            return false;
        }

        return $update->task->fae_id === $currentFaeId;
    }
}