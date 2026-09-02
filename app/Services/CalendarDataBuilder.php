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

        // Appointments for this month, grouped by day of month
        $apptsQuery = Appointment::with('fae')
            ->whereMonth('appointment_date', $month)
            ->whereYear('appointment_date', $year);

        if ($faeId !== null) {
            $apptsQuery->where('fae_id', $faeId);
        }

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

        foreach (AdminEvent::whereMonth('event_date', $month)
            ->whereYear('event_date', $year)
            ->orderBy('event_date', 'asc')
            ->get() as $evt) {
            if ($evt->event_date) {
                $day = (int)$evt->event_date->format('j');
                $adminEventsByDay[$day][] = $evt;

                if ($evt->category === 'busy') {
                    $busyDays[$day] = true;
                    $bookedDays[$day] = (object)['status' => 'busy'];
                }
            }
        }

        // Upcoming schedule
        $upcomingEvents = AdminEvent::where('event_date', '>=', Carbon::today()->format('Y-m-d'))
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