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
            // 1. Pending appointment requests
            $pendingAppts = Appointment::with('fae')
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($pendingAppts as $item) {
                $requester = $item->fae->name ?? ($item->user_name ?: 'FAE Member');
                $items[] = [
                    'type' => 'Appointment Request',
                    'title' => $requester . ': ' . $item->reason,
                    'description' => ($item->time_slot ? '[' . $item->time_slot . '] ' : '') . ($item->appointment_date ? $item->appointment_date->format('M d, Y') : ''),
                    'date' => $item->created_at ? $item->created_at->toIso8601String() : null,
                    'badge_color' => '#f59e0b',
                    'author_name' => $requester,
                    'status' => 'Pending',
                ];
            }

            // 2. FAE Progress Reports
            $reports = TaskUpdate::with('task')
                ->where('author_role', 'fae')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            foreach ($reports as $up) {
                $taskName = $up->task->task_name ?? 'Task';
                $items[] = [
                    'type' => 'FAE Progress Report',
                    'task_id' => $up->task_id,
                    'title' => $taskName,
                    'description' => $up->message,
                    'author_name' => $up->author_name,
                    'progress' => $up->progress_at_update,
                    'date' => $up->created_at ? $up->created_at->toIso8601String() : null,
                    'badge_color' => '#3b82f6',
                ];
            }
        } elseif ($faeId) {
            // 1. Appointment status updates & Admin Notes
            $faeAppts = Appointment::where('fae_id', $faeId)
                ->whereIn('status', ['accepted', 'rejected', 'rescheduled'])
                ->orderBy('updated_at', 'desc')
                ->take(6)
                ->get();

            foreach ($faeAppts as $appt) {
                $statusLabel = ucfirst($appt->status);
                $badgeColor = match($appt->status) {
                    'accepted' => '#16a34a',
                    'rejected' => '#dc2626',
                    'rescheduled' => '#f59e0b',
                    default => '#3b82f6'
                };

                $items[] = [
                    'type' => 'Appointment ' . $statusLabel,
                    'title' => 'Appointment ' . $statusLabel . ': ' . ($appt->reason ?: 'Meeting with Admin'),
                    'description' => ($appt->appointment_date ? $appt->appointment_date->format('M d, Y') : '') . ($appt->time_slot ? ' • ' . $appt->time_slot : ''),
                    'admin_notes' => $appt->admin_comment ?: null,
                    'date' => ($appt->updated_at ?: $appt->created_at) ? ($appt->updated_at ?: $appt->created_at)->toIso8601String() : null,
                    'badge_color' => $badgeColor,
                    'status' => $appt->status,
                ];
            }

            // 2. Admin Remarks & Reassignments on Assigned Tasks
            $adminNotes = TaskUpdate::with('task')
                ->where('fae_id', $faeId)
                ->where('author_role', 'admin')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            $notifiedTaskIds = [];

            foreach ($adminNotes as $an) {
                $taskName = $an->task->task_name ?? 'Task';
                $isReassignment = str_contains(strtolower($an->message), 'reassigned');
                $items[] = [
                    'type' => $isReassignment ? 'Task Reassigned' : 'Admin Remark',
                    'task_id' => $an->task_id,
                    'title' => $taskName,
                    'description' => $an->message,
                    'admin_notes' => $an->message,
                    'author_name' => 'Administrator',
                    'date' => $an->created_at ? $an->created_at->toIso8601String() : null,
                    'badge_color' => $isReassignment ? '#7c3aed' : '#dc2626',
                ];
                if ($an->task_id) {
                    $notifiedTaskIds[] = $an->task_id;
                }
            }

            // 3. Currently Assigned Tasks (only if not already notified by a recent reassignment/admin update)
            $assignedTasks = Task::where('fae_id', $faeId)
                ->whereIn('status', ['Pending', 'In Progress'])
                ->orderBy('created_at', 'desc')
                ->take(6)
                ->get();

            foreach ($assignedTasks as $item) {
                if (!in_array($item->id, $notifiedTaskIds, true)) {
                    $items[] = [
                        'type' => 'Assigned Task',
                        'task_id' => $item->id,
                        'title' => $item->task_name,
                        'description' => 'Deadline: ' . ($item->deadline ? $item->deadline->format('M d, Y') : 'No deadline set'),
                        'date' => $item->created_at ? $item->created_at->toIso8601String() : null,
                        'badge_color' => '#2563eb',
                        'priority' => $item->priority,
                    ];
                }
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
            foreach ($overdueQuery->take(5)->get() as $item) {
                $items[] = [
                    'type' => 'Overdue Task',
                    'task_id' => $item->id,
                    'title' => $item->task_name,
                    'description' => 'Task passed deadline on ' . ($item->deadline ? $item->deadline->format('M d, Y') : 'N/A'),
                    'date' => $item->deadline ? $item->deadline->endOfDay()->toIso8601String() : null,
                    'badge_color' => '#dc2626',
                ];
            }
        }

        // Sort all notifications by date descending
        usort($items, function ($a, $b) {
            $dateA = !empty($a['date']) ? strtotime($a['date']) : 0;
            $dateB = !empty($b['date']) ? strtotime($b['date']) : 0;
            return $dateB <=> $dateA;
        });

        return $items;
    }
}