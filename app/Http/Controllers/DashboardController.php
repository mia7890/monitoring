<?php

namespace App\Http\Controllers;

use App\Models\AdminEvent;
use App\Models\Appointment;
use App\Models\FaeUser;
use App\Models\Task;
use App\Services\MonitoringAuth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = MonitoringAuth::isAdmin();
        $currentFaeId = MonitoringAuth::faeId();
        $today = Carbon::today();
        $sevenDaysLater = Carbon::today()->addDays(7);

        // Counts
        $totalFAE = $isAdmin ? FaeUser::count() : 1;

        $taskBase = Task::query();
        if (!$isAdmin && $currentFaeId) {
            $taskBase->where('fae_id', $currentFaeId);
        }

        $totalTasks = (clone $taskBase)->count();
        $completedTasks = (clone $taskBase)->where('status', 'Completed')->count();
        $inProgressTasks = (clone $taskBase)->where('status', 'In Progress')->count();
        $pendingTasks = (clone $taskBase)->where('status', 'Pending')->count();
        $overdueTasks = (clone $taskBase)->where(function ($q) use ($today) {
            $q->where('status', 'Overdue')
              ->orWhere(function ($sub) use ($today) {
                  $sub->where('deadline', '<', $today->format('Y-m-d'))
                      ->where('status', '!=', 'Completed');
              });
        })->count();

        // Tasks for table
        $tasksQuery = Task::with(['fae', 'updates'])
            ->withCount('updates');
        if (!$isAdmin && $currentFaeId) {
            $tasksQuery->where('fae_id', $currentFaeId);
        }
        $tasks = $tasksQuery->orderBy('id', 'desc')->get();

        // Upcoming Items (Next 7 Days)
        $upcomingItems = [];

        // Tasks in next 7 days
        $upcomingTasksQuery = Task::whereNotNull('deadline')
            ->whereBetween('deadline', [$today->format('Y-m-d'), $sevenDaysLater->format('Y-m-d')])
            ->where('status', '!=', 'Completed');
        if (!$isAdmin && $currentFaeId) {
            $upcomingTasksQuery->where('fae_id', $currentFaeId);
        }
        foreach ($upcomingTasksQuery->get() as $t) {
            $upcomingItems[] = [
                'type' => 'task',
                'title' => $t->task_name,
                'date' => $t->deadline->format('Y-m-d'),
                'category' => 'task',
            ];
        }

        // Appointments
        $apptsQuery = Appointment::whereBetween('appointment_date', [$today->format('Y-m-d'), $sevenDaysLater->format('Y-m-d')]);
        if ($isAdmin) {
            $apptsQuery->where('status', 'pending');
        } else {
            $apptsQuery->where('status', 'accepted')->where('fae_id', $currentFaeId);
        }
        foreach ($apptsQuery->get() as $a) {
            $upcomingItems[] = [
                'type' => 'appointment',
                'title' => $a->reason,
                'date' => $a->appointment_date->format('Y-m-d'),
                'category' => $a->status,
            ];
        }

        // Admin events
        $eventsQuery = AdminEvent::whereBetween('event_date', [$today->format('Y-m-d'), $sevenDaysLater->format('Y-m-d')]);
        foreach ($eventsQuery->get() as $e) {
            $upcomingItems[] = [
                'type' => 'event',
                'title' => $e->title,
                'date' => $e->event_date->format('Y-m-d'),
                'category' => $e->category,
            ];
        }

        // Sort upcoming by date ascending
        usort($upcomingItems, fn($a, $b) => strcmp($a['date'], $b['date']));
        $upcomingItems = array_slice($upcomingItems, 0, 6);

        $faeList = FaeUser::orderBy('name', 'asc')->get();

        return view('dashboard', compact(
            'isAdmin',
            'currentFaeId',
            'totalFAE',
            'totalTasks',
            'completedTasks',
            'inProgressTasks',
            'pendingTasks',
            'overdueTasks',
            'tasks',
            'upcomingItems',
            'faeList'
        ));
    }
}
