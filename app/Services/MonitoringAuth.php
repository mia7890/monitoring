<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\FaeUser;
use App\Models\Task;
use App\Models\TaskUpdate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class MonitoringAuth
{
    public static function role(): ?string
    {
        return Session::get('monitoring_role');
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function isFae(): bool
    {
        return self::role() === 'fae';
    }

    public static function faeId(): ?int
    {
        return self::isFae() ? (int)Session::get('monitoring_fae_id') : null;
    }

    public static function faeName(): string
    {
        return (string)Session::get('monitoring_fae_name', 'FAE User');
    }

    public static function faeCode(): string
    {
        return (string)Session::get('monitoring_fae_code', '');
    }

    public static function adminKey(): string
    {
        return env('MONITORING_ADMIN_KEY', 'Admin12345!');
    }

    public static function notifications(): array
    {
        $items = [];
        $isAdmin = self::isAdmin();
        $faeId = self::faeId();

        if ($isAdmin) {
            // Pending appointments
            $pendingAppts = Appointment::where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($pendingAppts as $item) {
                $items[] = [
                    'type' => 'Appointment request',
                    'title' => $item->reason,
                    'date' => $item->appointment_date ? $item->appointment_date->format('Y-m-d') : null,
                ];
            }

            // Recent FAE Task Reports
            $reports = TaskUpdate::with('task')
                ->where('author_role', 'fae')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            foreach ($reports as $up) {
                $taskName = $up->task->task_name ?? 'Task';
                $items[] = [
                    'type' => 'FAE Progress Report',
                    'title' => $up->author_name . ' on "' . $taskName . '": ' . mb_substr($up->message, 0, 50),
                    'date' => $up->created_at ? $up->created_at->format('Y-m-d H:i') : null,
                ];
            }
        } elseif ($faeId) {
            // Assigned tasks
            $assignedTasks = Task::where('fae_id', $faeId)
                ->whereIn('status', ['Pending', 'In Progress'])
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($assignedTasks as $item) {
                $items[] = [
                    'type' => 'Assigned task',
                    'title' => $item->task_name,
                    'date' => $item->deadline ? $item->deadline->format('Y-m-d') : null,
                ];
            }

            // Admin notes on FAE tasks
            $adminNotes = TaskUpdate::whereHas('task', function ($q) use ($faeId) {
                    $q->where('fae_id', $faeId);
                })
                ->where('author_role', 'admin')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            foreach ($adminNotes as $an) {
                $taskName = $an->task->task_name ?? 'Task';
                $items[] = [
                    'type' => 'Admin Remark',
                    'title' => 'Admin on "' . $taskName . '": ' . mb_substr($an->message, 0, 50),
                    'date' => $an->created_at ? $an->created_at->format('Y-m-d H:i') : null,
                ];
            }
        }

        // Overdue tasks
        $today = Carbon::today()->format('Y-m-d');
        $overdueQuery = Task::where(function ($q) use ($today) {
            $q->where('status', 'Overdue')
              ->orWhere(function ($sub) use ($today) {
                  $sub->where('deadline', '<', $today)
                      ->where('status', '!=', 'Completed');
              });
        });

        if (!$isAdmin && $faeId) {
            $overdueQuery->where('fae_id', $faeId);
        }

        if ($isAdmin || $faeId) {
            foreach ($overdueQuery->get() as $item) {
                $items[] = [
                    'type' => 'Overdue task',
                    'title' => $item->task_name,
                    'date' => $item->deadline ? $item->deadline->format('Y-m-d') : null,
                ];
            }
        }

        return $items;
    }

    public static function currentProfileImage(): ?string
    {
        if (self::isAdmin()) {
            return Session::get('monitoring_admin_profile_image');
        }
        $faeId = self::faeId();
        if ($faeId) {
            $fae = FaeUser::find($faeId);
            return $fae ? $fae->profile_image : null;
        }
        return null;
    }
}
