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
            ->whereDate('deadline', '>=', $today->format('Y-m-d'))
            ->whereDate('deadline', '<=', $sevenDaysLater->format('Y-m-d'))
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
        $apptsQuery = Appointment::whereDate('appointment_date', '>=', $today->format('Y-m-d'))
            ->whereDate('appointment_date', '<=', $sevenDaysLater->format('Y-m-d'));
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
                'time_slot' => $a->time_slot,
                'category' => $a->status,
            ];
        }

        // Admin events
        $eventsQuery = AdminEvent::whereDate('event_date', '>=', $today->format('Y-m-d'))
            ->whereDate('event_date', '<=', $sevenDaysLater->format('Y-m-d'));
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

        $progressChartData = $this->buildChartData($taskBase);

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
            'faeList',
            'progressChartData'
        ));
    }

    /**
     * Build chart datasets for Day (14 days), Week (Month weeks), and Month (Year months).
     */
    private function buildChartData($taskBase): array
    {
        $now = Carbon::now();

        // 1. DAY MODE: Last 14 days
        $dayLabels = [];
        $dayCompleted = [];
        $dayTotal = [];
        for ($i = 13; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i);
            $dayLabels[] = $date->format('d.m');
            $cutoff = $date->copy()->endOfDay();

            $total = (clone $taskBase)
                ->where(function ($q) use ($cutoff) {
                    $q->whereNull('created_at')
                      ->orWhere('created_at', '<=', $cutoff);
                })->count();

            $completed = (clone $taskBase)
                ->where('status', 'Completed')
                ->where(function ($q) use ($cutoff) {
                    $q->whereNull('updated_at')
                      ->orWhere('updated_at', '<=', $cutoff);
                })->count();

            $dayTotal[] = $total;
            $dayCompleted[] = $completed;
        }

        // 2. WEEK MODE: Weeks of this month
        $daysThisMonth = $now->daysInMonth;
        $weekLabels = [];
        $weekCompleted = [];
        $weekTotal = [];

        $weeks = [
            ['name' => 'Week 1', 'end' => 7],
            ['name' => 'Week 2', 'end' => 14],
            ['name' => 'Week 3', 'end' => 21],
            ['name' => 'Week 4', 'end' => 28],
        ];
        if ($daysThisMonth > 28) {
            $weeks[] = ['name' => 'Week 5', 'end' => $daysThisMonth];
        }

        foreach ($weeks as $w) {
            $weekLabels[] = $w['name'];
            $cutoff = Carbon::create($now->year, $now->month, min($w['end'], $daysThisMonth))->endOfDay();
            $effectiveCutoff = $cutoff->isFuture() ? $now->copy()->endOfDay() : $cutoff;

            $total = (clone $taskBase)
                ->where(function ($q) use ($effectiveCutoff) {
                    $q->whereNull('created_at')
                      ->orWhere('created_at', '<=', $effectiveCutoff);
                })->count();

            $completed = (clone $taskBase)
                ->where('status', 'Completed')
                ->where(function ($q) use ($effectiveCutoff) {
                    $q->whereNull('updated_at')
                      ->orWhere('updated_at', '<=', $effectiveCutoff);
                })->count();

            $weekTotal[] = $total;
            $weekCompleted[] = $completed;
        }

        // 3. MONTH MODE: 12 Months of this year
        $monthLabels = [];
        $monthCompleted = [];
        $monthTotal = [];
        for ($m = 1; $m <= 12; $m++) {
            $mDate = Carbon::create($now->year, $m, 1);
            $monthLabels[] = $mDate->format('M');

            $monthEnd = $mDate->copy()->endOfMonth();
            $effectiveCutoff = $monthEnd->isFuture() ? $now->copy()->endOfDay() : $monthEnd;

            $total = (clone $taskBase)
                ->where(function ($q) use ($effectiveCutoff) {
                    $q->whereNull('created_at')
                      ->orWhere('created_at', '<=', $effectiveCutoff);
                })->count();

            $completed = (clone $taskBase)
                ->where('status', 'Completed')
                ->where(function ($q) use ($effectiveCutoff) {
                    $q->whereNull('updated_at')
                      ->orWhere('updated_at', '<=', $effectiveCutoff);
                })->count();

            $monthTotal[] = $total;
            $monthCompleted[] = $completed;
        }

        return [
            'day' => [
                'labels' => $dayLabels,
                'completed' => $dayCompleted,
                'total' => $dayTotal,
                'subtext' => $now->copy()->subDays(13)->format('M d') . ' – ' . $now->format('M d, Y'),
            ],
            'week' => [
                'labels' => $weekLabels,
                'completed' => $weekCompleted,
                'total' => $weekTotal,
                'subtext' => $now->format('F Y') . ' (Weekly Progress)',
            ],
            'month' => [
                'labels' => $monthLabels,
                'completed' => $monthCompleted,
                'total' => $monthTotal,
                'subtext' => 'Jan – Dec ' . $now->format('Y') . ' (Monthly Overview)',
            ],
        ];
    }
}
