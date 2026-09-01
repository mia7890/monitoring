<?php

namespace App\Http\Controllers;

use App\Models\AdminEvent;
use App\Models\Appointment;
use App\Models\Task;
use App\Services\MonitoringAuth;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = MonitoringAuth::isAdmin();
        $currentFaeId = MonitoringAuth::faeId();

        $month = (int)$request->query('month', date('n'));
        $year = (int)$request->query('year', date('Y'));

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

        // Fetch task deadlines for this month
        $taskQuery = Task::with('fae')
            ->whereMonth('deadline', $month)
            ->whereYear('deadline', $year);

        if (!$isAdmin && $currentFaeId) {
            $taskQuery->where('fae_id', $currentFaeId);
        }

        $monthTasks = $taskQuery->orderBy('deadline', 'asc')->get();
        $tasksByDay = [];
        foreach ($monthTasks as $task) {
            $day = (int)$task->deadline->format('j');
            $tasksByDay[$day][] = $task;
        }

        // Fetch appointments for this month
        $apptQuery = Appointment::with('fae')
            ->whereMonth('appointment_date', $month)
            ->whereYear('appointment_date', $year);

        if (!$isAdmin && $currentFaeId) {
            $apptQuery->where('fae_id', $currentFaeId);
        }

        $monthAppts = $apptQuery->orderBy('appointment_date', 'asc')->get();
        $apptsByDay = [];
        $bookedDays = [];

        foreach ($monthAppts as $appt) {
            $day = (int)$appt->appointment_date->format('j');
            $apptsByDay[$day][] = $appt;
            if (in_array($appt->status, ['pending', 'accepted'], true)) {
                $bookedDays[$day] = $appt;
            }
        }

        // Fetch admin events for this month
        $adminEvents = AdminEvent::whereMonth('event_date', $month)
            ->whereYear('event_date', $year)
            ->orderBy('event_date', 'asc')
            ->get();

        $adminEventsByDay = [];
        $busyDays = [];

        foreach ($adminEvents as $evt) {
            $day = (int)$evt->event_date->format('j');
            $adminEventsByDay[$day][] = $evt;
            if ($evt->category === 'busy') {
                $busyDays[$day] = true;
                $bookedDays[$day] = (object)['status' => 'busy'];
            }
        }

        // Upcoming schedule
        $upcomingEvents = AdminEvent::where('event_date', '>=', Carbon::today()->format('Y-m-d'))
            ->orderBy('event_date', 'asc')
            ->take(10)
            ->get();

        $upcomingAppointments = Appointment::with('fae')
            ->where('appointment_date', '>=', Carbon::today()->format('Y-m-d'));

        if (!$isAdmin && $currentFaeId) {
            $upcomingAppointments->where('fae_id', $currentFaeId);
        }

        $upcomingApptList = $upcomingAppointments->orderBy('appointment_date', 'asc')->take(10)->get();

        return view('calendar.index', compact(
            'isAdmin',
            'currentFaeId',
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
            'upcomingEvents',
            'upcomingApptList'
        ));
    }

    public function bookAppointment(Request $request)
    {
        $isAdmin = MonitoringAuth::isAdmin();
        $currentFaeId = MonitoringAuth::faeId();

        if ($isAdmin) {
            return back()->with('error', 'Admin users cannot request appointments.');
        }

        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'reason' => 'required|string',
        ]);

        $apptDate = $request->input('appointment_date');
        $reason = trim($request->input('reason'));
        $userName = MonitoringAuth::faeName();

        // Check if date is already booked (pending or accepted)
        $alreadyBooked = Appointment::where('appointment_date', $apptDate)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        $adminBusy = AdminEvent::where('event_date', $apptDate)
            ->where('category', 'busy')
            ->exists();

        if ($alreadyBooked || $adminBusy) {
            $msg = $adminBusy
                ? 'The admin is unavailable on this date. Please choose another date.'
                : 'This date is already booked! Please select another date.';
            return back()->with('error', $msg);
        }

        Appointment::create([
            'fae_id' => $currentFaeId,
            'user_name' => $userName,
            'appointment_date' => $apptDate,
            'reason' => $reason,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Appointment request submitted successfully!');
    }

    public function updateAppointmentStatus(Request $request)
    {
        $request->validate([
            'appt_id' => 'required|exists:appointments,id',
            'status' => 'required|in:accepted,rejected',
            'admin_comment' => 'nullable|string',
        ]);

        $appt = Appointment::findOrFail($request->input('appt_id'));
        $newStatus = $request->input('status');
        $adminComment = $request->input('admin_comment');

        if ($newStatus === 'accepted') {
            $appt->update([
                'status' => 'accepted',
                'admin_comment' => null,
            ]);

            // Reject other pending requests for the same date
            Appointment::where('appointment_date', $appt->appointment_date)
                ->where('id', '!=', $appt->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'admin_comment' => 'Date booked by another user',
                ]);

            return back()->with('success', 'Appointment accepted! Other requests on this day were automatically rejected.');
        } else {
            $appt->update([
                'status' => 'rejected',
                'admin_comment' => $adminComment ?: 'Rejected by administrator',
            ]);

            return back()->with('success', 'Appointment rejected with comment.');
        }
    }

    public function storeEvent(Request $request)
    {
        $request->validate([
            'event_title' => 'required|string|max:255',
            'event_date' => 'required|date',
            'event_category' => 'required|in:meeting,busy,reminder,other',
            'event_description' => 'nullable|string',
        ]);

        AdminEvent::create([
            'title' => trim($request->input('event_title')),
            'event_date' => $request->input('event_date'),
            'description' => $request->input('event_description') ?: null,
            'category' => $request->input('event_category', 'other'),
        ]);

        return back()->with('success', 'Upcoming event added successfully!');
    }

    public function destroyEvent(Request $request)
    {
        $eventId = (int)$request->input('event_id');
        $event = AdminEvent::findOrFail($eventId);
        $event->delete();

        return back()->with('success', 'Event deleted successfully.');
    }
}
