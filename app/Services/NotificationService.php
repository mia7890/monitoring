<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Task;
use App\Models\TaskUpdate;
use Carbon\Carbon;

class NotificationService
{
    /**
     * Build the notification list shown in the app header for the current
     * monitoring session role.
     */
    public static function notifications(): array
    {
        $items = [];
        $isAdmin = MonitoringAuth::isAdmin();
        $faeId = MonitoringAuth::faeId();

        if ($isAdmin) {
            $pendingAppts = Appointment::where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($pendingAppts as $item) {
                $items[] = [
                    'type' => 'Appointment request',
                    'title' => ($item->time_slot ? '[' . $item->time_slot . '] ' : '') . $item->reason,
                    'date' => $item->appointment_date ? $item->appointment_date->format('Y-m-d') : null,
                ];
            }

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
}