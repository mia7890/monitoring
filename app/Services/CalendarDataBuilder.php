<?php

namespace App\Services;

use App\Models\AdminEvent;
use App\Models\Appointment;
use App\Models\Task;
use Carbon\Carbon;

class CalendarDataBuilder
{
    /**
     * Build the data structures shared by the landing page and the
     * authenticated calendar view for a given month.
     *
     * @param int      $month Target month (1-12)
     * @param int      $year  Target year
     * @param int|null $faeId When provided, only tasks/appointments assigned
     *                        to this FAE are included; null shows everything.
     * @param int      $upcomingEventsTake Number of upcoming events to return
     * @param bool     $includeUpcomingAppointments Also build the upcoming
     *                                             appointments list
     */
    public static function build(
        int $month,
        int $year,
        ?int $faeId = null,
        int $upcomingEventsTake = 10,
        bool $includeUpcomingAppointments = false
    ): array {
        if ($month < 1) {
            $month = 12;
            $year--;
        } elseif ($month > 12) {
            $month = 1;
            $year++;
        }

        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }

        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }

        // Tasks for this month, grouped by day of month
        $tasksQuery = Task::with('fae')
            ->whereMonth('deadline', $month)
            ->whereYear('deadline', $year);

        if ($faeId !== null) {
            $tasksQuery->where('fae_id', $faeId);
        }

        $tasksByDay = [];
        foreach ($tasksQuery->orderBy('deadline', 'asc')->get() as $task) {
            if ($task->deadline) {
                $tasksByDay[(int)$task->deadline->format('j')][] = $task;
            }
        }

        // Appointments for this month, grouped by day of month (visible to all FAEs so they see booked slots)
        $apptsQuery = Appointment::with('fae')
            ->whereMonth('appointment_date', $month)
            ->whereYear('appointment_date', $year);

        $apptsByDay = [];
        $bookedDays = [];

        foreach ($apptsQuery->orderBy('appointment_date', 'asc')->get() as $appt) {
            if ($appt->appointment_date) {
                $day = (int)$appt->appointment_date->format('j');
                $apptsByDay[$day][] = $appt;

                if (in_array($appt->status, ['pending', 'accepted'], true)) {
                    $bookedDays[$day] = $appt;
                }
            }
        }

        // Admin events for this month, grouped by day of month
        $adminEventsByDay = [];
        $busyDays = [];

        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth = (clone $startOfMonth)->endOfMonth();

        $adminEvents = AdminEvent::where(function($query) use ($startOfMonth, $endOfMonth) {
            $query->whereBetween('event_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
                  ->orWhere(function($q2) use ($startOfMonth, $endOfMonth) {
                      $q2->whereNotNull('end_date')
                         ->where('event_date', '<=', $endOfMonth->format('Y-m-d'))
                         ->where('end_date', '>=', $startOfMonth->format('Y-m-d'));
                  });
        })->orderBy('event_date', 'asc')->get();

        foreach ($adminEvents as $evt) {
            if ($evt->event_date) {
                $evtStart = clone $evt->event_date;
                $evtEnd = $evt->end_date ? clone $evt->end_date : clone $evtStart;

                $plotStart = $evtStart->max($startOfMonth);
                $plotEnd = $evtEnd->min($endOfMonth);

                for ($d = clone $plotStart; $d <= $plotEnd; $d->modify('+1 day')) {
                    $day = (int)$d->format('j');
                    $adminEventsByDay[$day][] = $evt;

                    if ($evt->category === 'busy') {
                        $busyDays[$day] = true;
                        $bookedDays[$day] = (object)['status' => 'busy'];
                    }
                }
            }
        }

        // Upcoming schedule
        $upcomingEvents = AdminEvent::where('event_date', '>=', Carbon::today()->format('Y-m-d'))
            ->orWhere(function($q) {
                $q->whereNotNull('end_date')
                  ->where('end_date', '>=', Carbon::today()->format('Y-m-d'));
            })
            ->orderBy('event_date', 'asc')
            ->take($upcomingEventsTake)
            ->get();

        $data = compact(
            'month',
            'year',
            'prevMonth',
            'prevYear',
            'nextMonth',
            'nextYear',
            'tasksByDay',
            'apptsByDay',
            'bookedDays',
            'adminEventsByDay',
            'busyDays',
            'upcomingEvents'
        );

        if ($includeUpcomingAppointments) {
            $appointmentsQuery = Appointment::with('fae')
                ->where('appointment_date', '>=', Carbon::today()->format('Y-m-d'));

            if ($faeId !== null) {
                $appointmentsQuery->where('fae_id', $faeId);
            }

            $data['upcomingApptList'] = $appointmentsQuery
                ->orderBy('appointment_date', 'asc')
                ->take(10)
                ->get();
        }

        return $data;
    }
}