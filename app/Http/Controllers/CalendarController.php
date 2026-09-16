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
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'reason' => 'required|string',
        ]);

        $apptDate = $request->input('appointment_date');
        $rawStart = $request->input('start_time');
        $rawEnd = $request->input('end_time');
        $startTime = strlen($rawStart) === 5 ? $rawStart . ':00' : $rawStart;
        $endTime = strlen($rawEnd) === 5 ? $rawEnd . ':00' : $rawEnd;
        $reason = trim($request->input('reason'));
        $userName = MonitoringAuth::faeName();

        // Check if admin is busy on this date
        $adminBusy = AdminEvent::where('category', 'busy')
            ->where(function ($query) use ($apptDate) {
                $query->whereDate('event_date', $apptDate)
                      ->orWhere(function ($q) use ($apptDate) {
                          $q->whereNotNull('end_date')
                            ->whereDate('event_date', '<=', $apptDate)
                            ->whereDate('end_date', '>=', $apptDate);
                      });
            })
            ->exists();

        if ($adminBusy) {
            return back()->with('error', 'The admin is unavailable on this date. Please choose another date.');
        }

        // Check if this FAE already has an appointment on this date (pending, accepted, or rejected)
        $existingFaeAppt = Appointment::where('fae_id', $currentFaeId)
            ->whereDate('appointment_date', $apptDate)
            ->whereIn('status', ['pending', 'accepted', 'rejected'])
            ->first();

        if ($existingFaeAppt) {
            $slotDisplay = $existingFaeAppt->time_slot ?: ($existingFaeAppt->start_time . ' - ' . $existingFaeAppt->end_time);
            return back()->with('error', "You already have an appointment ({$existingFaeAppt->status} - {$slotDisplay}) on this date. You cannot double book on the same day.");
        }

        // Check if the requested time slot overlaps with an existing pending or accepted appointment
        $conflictingAppointment = Appointment::whereDate('appointment_date', $apptDate)
            ->whereIn('status', ['pending', 'accepted'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereNull('start_time')
                      ->orWhereNull('end_time')
                      ->orWhere(function ($q) use ($startTime, $endTime) {
                          $q->where('start_time', '<', $endTime)
                            ->where('end_time', '>', $startTime);
                      });
            })
            ->first();

        if ($conflictingAppointment) {
            $slotDisplay = $conflictingAppointment->time_slot;
            $msg = $slotDisplay
                ? "This time slot conflicts with an existing {$conflictingAppointment->status} appointment ({$slotDisplay}). Please select another time or date."
                : 'This time slot conflicts with an existing appointment on this date. Please select another time or date.';
            return back()->with('error', $msg);
        }

        Appointment::create([
            'fae_id' => $currentFaeId,
            'user_name' => $userName,
            'appointment_date' => $apptDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
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

            // Reject ONLY other pending requests for the same date that overlap with this accepted appointment
            $conflictQuery = Appointment::whereDate('appointment_date', $appt->appointment_date)
                ->where('id', '!=', $appt->id)
                ->where('status', 'pending');

            if ($appt->start_time && $appt->end_time) {
                $conflictQuery->where(function ($query) use ($appt) {
                    $query->whereNull('start_time')
                          ->orWhereNull('end_time')
                          ->orWhere(function ($q) use ($appt) {
                              $q->where('start_time', '<', $appt->end_time)
                                ->where('end_time', '>', $appt->start_time);
                          });
                });
            }

            $conflictQuery->update([
                'status' => 'rejected',
                'admin_comment' => 'Time slot conflicts with an accepted appointment',
            ]);

            return back()->with('success', 'Appointment accepted! Any overlapping pending requests on this day were automatically rejected.');
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
            'event_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:event_date',
            'event_category' => 'required|in:meeting,busy,reminder,other',
            'event_description' => 'nullable|string',
        ]);

        AdminEvent::create([
            'title' => trim($request->input('event_title')),
            'event_date' => $request->input('event_date'),
            'end_date' => $request->input('end_date'),
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
    public function destroyAppointment(Request $request)
    {
        $isAdmin = MonitoringAuth::isAdmin();
        $currentFaeId = MonitoringAuth::faeId();

        $apptId = (int)$request->input('appt_id');
        $appt = Appointment::findOrFail($apptId);

        // Authorization: Non-admin FAE can only delete their own appointments
        if (!$isAdmin && (int)$appt->fae_id !== (int)$currentFaeId) {
            return back()->with('error', 'You can only delete your own appointments.');
        }

        $isPast = $appt->appointment_date < date('Y-m-d');
        $isCancelled = $appt->status === 'cancelled';

        if (!$isPast && !$isCancelled) {
            return back()->with('error', 'Only cancelled or expired appointments can be deleted.');
        }

        $appt->delete();

        return back()->with('success', 'Appointment deleted successfully.');
    }


    public function cancelAppointment(Request $request)
    {
        $isAdmin = MonitoringAuth::isAdmin();
        $currentFaeId = MonitoringAuth::faeId();

        $request->validate([
            'appt_id' => 'required|exists:appointments,id',
        ]);

        $appt = Appointment::findOrFail($request->input('appt_id'));

        // Authorization: Non-admin FAE can only cancel their own appointment
        if (!$isAdmin && (int)$appt->fae_id !== (int)$currentFaeId) {
            return back()->with('error', 'You can only cancel your own appointments.');
        }

        // Only pending, accepted, or rejected appointments can be cancelled
        if (!in_array($appt->status, ['pending', 'accepted', 'rejected'], true)) {
            return back()->with('error', 'Only pending, accepted, or rejected appointments can be cancelled.');
        }


        // Cannot cancel past appointments
        if ($appt->appointment_date < date('Y-m-d')) {
            return back()->with('error', 'Past appointments cannot be cancelled.');
        }

        $appt->update([
            'status' => 'cancelled',
        ]);

        return back()->with('success', 'Appointment cancelled successfully.');
    }
}

