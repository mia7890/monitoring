<?php

namespace App\Http\Controllers;

use App\Models\AdminEvent;
use App\Models\Appointment;
use App\Services\CalendarDataBuilder;
use App\Services\MonitoringAuth;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $isAdmin = MonitoringAuth::isAdmin();
        $currentFaeId = MonitoringAuth::faeId();

        $month = (int)$request->query('month', date('n'));
        $year = (int)$request->query('year', date('Y'));

        $data = array_merge(
            compact('isAdmin', 'currentFaeId'),
            CalendarDataBuilder::build($month, $year, $isAdmin ? null : $currentFaeId, 10, true)
        );

        return view('calendar.index', $data);
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
        $alreadyBooked = Appointment::whereDate('appointment_date', $apptDate)
            ->whereIn('status', ['pending', 'accepted'])
            ->exists();

        $adminBusy = AdminEvent::whereDate('event_date', $apptDate)
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
            Appointment::whereDate('appointment_date', $appt->appointment_date)
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
