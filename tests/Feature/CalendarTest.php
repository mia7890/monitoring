<?php

namespace Tests\Feature;

use App\Models\AdminEvent;
use App\Models\Appointment;
use App\Models\FaeUser;
use Carbon\Carbon;
use Tests\TestCase;

class CalendarTest extends TestCase
{
    public function test_guest_cannot_book_appointment(): void
    {
        $this->post('/calendar/book', [
            'appointment_date' => Carbon::tomorrow()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'reason' => 'Test appointment',
        ])->assertRedirect('/access');
    }

    public function test_fae_can_book_appointment_with_time_slot(): void
    {
        $fae = FaeUser::create([
            'name' => 'Booking Fae',
            'fae_code' => 'BKG-1',
        ]);

        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $fae->id, 'monitoring_fae_name' => $fae->name])
            ->post('/calendar/book', [
                'appointment_date' => Carbon::tomorrow()->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '12:00',
                'reason' => 'Home visit',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'fae_id' => $fae->id,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Home visit',
        ]);
    }

    public function test_admin_cannot_book_appointment(): void
    {
        $this->withSession(['monitoring_role' => 'admin'])
            ->post('/calendar/book', [
                'appointment_date' => Carbon::tomorrow()->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '12:00',
                'reason' => 'Admin request',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Admin users cannot request appointments.');
    }

    public function test_fae_cannot_book_overlapping_time_slot(): void
    {
        $faeA = FaeUser::create(['name' => 'Fae A', 'fae_code' => 'BKG-A']);
        $faeB = FaeUser::create(['name' => 'Fae B', 'fae_code' => 'BKG-B']);

        $date = Carbon::tomorrow()->format('Y-m-d');

        // FAE A already booked 09:00 - 12:00
        Appointment::create([
            'fae_id' => $faeA->id,
            'user_name' => 'Fae A',
            'appointment_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Morning consultation',
            'status' => 'pending',
        ]);

        $this->assertDatabaseCount('appointments', 1);

        // FAE B tries booking overlapping slot 10:00 - 11:30
        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $faeB->id, 'monitoring_fae_name' => 'Fae B'])
            ->post('/calendar/book', [
                'appointment_date' => $date,
                'start_time' => '10:00',
                'end_time' => '11:30',
                'reason' => 'Overlap Conflict',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_same_fae_cannot_double_book_on_same_date(): void
    {
        $fae = FaeUser::create(['name' => 'Fae Single', 'fae_code' => 'SGL-1']);
        $date = Carbon::tomorrow()->format('Y-m-d');

        // First appointment
        Appointment::create([
            'fae_id' => $fae->id,
            'user_name' => $fae->name,
            'appointment_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'First consultation',
            'status' => 'pending',
        ]);

        // Same FAE tries booking another non-overlapping time slot on same day
        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $fae->id, 'monitoring_fae_name' => $fae->name])
            ->post('/calendar/book', [
                'appointment_date' => $date,
                'start_time' => '14:00',
                'end_time' => '16:00',
                'reason' => 'Second consultation attempt',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_fae_can_book_non_overlapping_time_slot_on_same_date(): void
    {
        $faeA = FaeUser::create(['name' => 'Fae A', 'fae_code' => 'SLOT-A']);
        $faeB = FaeUser::create(['name' => 'Fae B', 'fae_code' => 'SLOT-B']);

        $date = Carbon::tomorrow()->format('Y-m-d');

        // FAE A booked 09:00 - 12:00
        Appointment::create([
            'fae_id' => $faeA->id,
            'user_name' => 'Fae A',
            'appointment_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Morning Slot',
            'status' => 'accepted',
        ]);

        // FAE B books 13:00 - 16:00 (1pm onwards) on the same date
        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $faeB->id, 'monitoring_fae_name' => 'Fae B'])
            ->post('/calendar/book', [
                'appointment_date' => $date,
                'start_time' => '13:00',
                'end_time' => '16:00',
                'reason' => 'Afternoon Slot',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('appointments', 2);
        $this->assertDatabaseHas('appointments', [
            'fae_id' => $faeB->id,
            'start_time' => '13:00:00',
            'end_time' => '16:00:00',
            'reason' => 'Afternoon Slot',
        ]);
    }

    public function test_fae_can_book_consecutive_adjacent_time_slot(): void
    {
        $faeA = FaeUser::create(['name' => 'Fae A', 'fae_code' => 'ADJ-A']);
        $faeB = FaeUser::create(['name' => 'Fae B', 'fae_code' => 'ADJ-B']);

        $date = Carbon::tomorrow()->format('Y-m-d');

        // FAE A booked 09:00 - 12:00
        Appointment::create([
            'fae_id' => $faeA->id,
            'user_name' => 'Fae A',
            'appointment_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Morning Slot',
            'status' => 'accepted',
        ]);

        // FAE B books exactly starting at 12:00 to 14:00 (consecutive, non-overlapping)
        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $faeB->id, 'monitoring_fae_name' => 'Fae B'])
            ->post('/calendar/book', [
                'appointment_date' => $date,
                'start_time' => '12:00',
                'end_time' => '14:00',
                'reason' => 'Consecutive Slot',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_fae_cannot_book_date_when_admin_is_busy(): void
    {
        $fae = FaeUser::create(['name' => 'Busy Fae', 'fae_code' => 'BZY-1']);

        $date = Carbon::tomorrow()->format('Y-m-d');

        AdminEvent::create([
            'title' => 'Field Trip',
            'event_date' => $date,
            'category' => 'busy',
        ]);

        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $fae->id])
            ->post('/calendar/book', [
                'appointment_date' => $date,
                'start_time' => '09:00',
                'end_time' => '12:00',
                'reason' => 'Should fail',
            ])
            ->assertSessionHas('error', 'The admin is unavailable on this date. Please choose another date.');
    }

    public function test_admin_accepting_appointment_rejects_only_overlapping_pending_requests(): void
    {
        $faeA = FaeUser::create(['name' => 'Fae A', 'fae_code' => 'ACC-A']);
        $faeB = FaeUser::create(['name' => 'Fae B', 'fae_code' => 'ACC-B']);
        $faeC = FaeUser::create(['name' => 'Fae C', 'fae_code' => 'ACC-C']);

        $date = Carbon::tomorrow()->format('Y-m-d');

        // Appt A: 09:00 - 12:00
        $apptA = Appointment::create([
            'fae_id' => $faeA->id,
            'user_name' => 'Fae A',
            'appointment_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Morning 9-12',
            'status' => 'pending',
        ]);

        // Appt B: 10:00 - 11:00 (Overlaps with A)
        $apptB = Appointment::create([
            'fae_id' => $faeB->id,
            'user_name' => 'Fae B',
            'appointment_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'reason' => 'Mid morning 10-11',
            'status' => 'pending',
        ]);

        // Appt C: 13:00 - 15:00 (Does NOT overlap with A)
        $apptC = Appointment::create([
            'fae_id' => $faeC->id,
            'user_name' => 'Fae C',
            'appointment_date' => $date,
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'reason' => 'Afternoon 1-3',
            'status' => 'pending',
        ]);

        // Admin accepts Appt A (09:00 - 12:00)
        $this->withSession(['monitoring_role' => 'admin'])
            ->post('/calendar/appointment-status', [
                'appt_id' => $apptA->id,
                'status' => 'accepted',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        // Appt A is accepted
        $this->assertDatabaseHas('appointments', ['id' => $apptA->id, 'status' => 'accepted']);
        // Appt B (overlapping) was auto-rejected
        $this->assertDatabaseHas('appointments', ['id' => $apptB->id, 'status' => 'rejected']);
        // Appt C (non-overlapping afternoon) is STILL pending
        $this->assertDatabaseHas('appointments', ['id' => $apptC->id, 'status' => 'pending']);
    }

    public function test_fae_cannot_see_other_fae_appointment_details_but_sees_slot_taken(): void
    {
        $faeA = FaeUser::create(['name' => 'Fae Alpha', 'fae_code' => 'VIEW-A']);
        $faeB = FaeUser::create(['name' => 'Fae Beta', 'fae_code' => 'VIEW-B']);

        $date = Carbon::now()->addDays(3);

        Appointment::create([
            'fae_id' => $faeA->id,
            'user_name' => 'Fae Alpha',
            'appointment_date' => $date->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Alpha Review Session',
            'status' => 'accepted',
        ]);

        // FAE B views calendar
        $response = $this->withSession([
            'monitoring_role' => 'fae',
            'monitoring_fae_id' => $faeB->id,
            'monitoring_fae_name' => $faeB->name,
        ])->get('/calendar?month=' . $date->month . '&year=' . $date->year);

        $response->assertStatus(200);
        // FAE B sees time slot is taken
        $response->assertSee('09:00 AM - 12:00 PM');
        // FAE B does NOT see other FAE's private details
        $response->assertDontSee('Fae Alpha');
        $response->assertDontSee('Alpha Review Session');

        // Admin views calendar and CAN see all details
        $adminResponse = $this->withSession([
            'monitoring_role' => 'admin',
        ])->get('/calendar?month=' . $date->month . '&year=' . $date->year);

        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('Fae Alpha');
        $adminResponse->assertSee('Alpha Review Session');
    }

    public function test_past_appointments_are_marked_expired_and_kept_for_reports(): void
    {
        $fae = FaeUser::create(['name' => 'Report Fae', 'fae_code' => 'RPT-1']);
        $pastDate = Carbon::yesterday()->format('Y-m-d');

        // Past pending appointment
        $pendingAppt = Appointment::create([
            'fae_id' => $fae->id,
            'user_name' => $fae->name,
            'appointment_date' => $pastDate,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Old pending consultation',
            'status' => 'pending',
        ]);

        // Past accepted appointment (must NOT be deleted)
        $acceptedAppt = Appointment::create([
            'fae_id' => $fae->id,
            'user_name' => $fae->name,
            'appointment_date' => $pastDate,
            'start_time' => '13:00:00',
            'end_time' => '16:00:00',
            'reason' => 'Completed historic visit',
            'status' => 'accepted',
        ]);

        $this->artisan('appointments:expire')->assertSuccessful();

        // Past pending appointment is marked as expired, NOT deleted
        $this->assertDatabaseHas('appointments', [
            'id' => $pendingAppt->id,
            'status' => 'expired',
        ]);

        // Past accepted appointment is fully preserved for reports
        $this->assertDatabaseHas('appointments', [
            'id' => $acceptedAppt->id,
            'status' => 'accepted',
        ]);
    }

    public function test_admin_cannot_create_event_on_past_date(): void
    {
        $pastDate = Carbon::yesterday()->format('Y-m-d');

        $this->withSession(['monitoring_role' => 'admin'])
            ->post('/calendar/events', [
                'event_title' => 'Past Executive Meeting',
                'event_date' => $pastDate,
                'end_date' => $pastDate,
                'event_category' => 'meeting',
            ])
            ->assertSessionHasErrors(['event_date']);

        $this->assertDatabaseMissing('admin_events', [
            'title' => 'Past Executive Meeting',
        ]);
    }

    public function test_fae_cannot_book_appointment_on_past_date(): void
    {
        $fae = FaeUser::create(['name' => 'Past Booking FAE', 'fae_code' => 'PST-1']);
        $pastDate = Carbon::yesterday()->format('Y-m-d');

        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $fae->id, 'monitoring_fae_name' => $fae->name])
            ->post('/calendar/book', [
                'appointment_date' => $pastDate,
                'start_time' => '09:00',
                'end_time' => '12:00',
                'reason' => 'Attempted past booking',
            ])
            ->assertSessionHasErrors(['appointment_date']);

        $this->assertDatabaseMissing('appointments', [
            'fae_id' => $fae->id,
            'reason' => 'Attempted past booking',
        ]);
    }

    public function test_fae_cannot_rebook_after_rejection_on_same_date(): void
    {
        $fae = FaeUser::create(['name' => 'Rejected Fae', 'fae_code' => 'REJ-1']);
        $date = Carbon::tomorrow()->format('Y-m-d');

        // FAE already has a rejected appointment on this date
        Appointment::create([
            'fae_id' => $fae->id,
            'user_name' => $fae->name,
            'appointment_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Original request',
            'status' => 'rejected',
            'admin_comment' => 'Not available',
        ]);

        $this->assertDatabaseCount('appointments', 1);

        // Same FAE tries to rebook on the same date (even different time)
        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $fae->id, 'monitoring_fae_name' => $fae->name])
            ->post('/calendar/book', [
                'appointment_date' => $date,
                'start_time' => '14:00',
                'end_time' => '16:00',
                'reason' => 'Retry after rejection',
            ])
            ->assertSessionHas('error');

        // No new appointment should have been created
        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_fae_can_cancel_own_pending_appointment(): void
    {
        $fae = FaeUser::create(['name' => 'Cancel Fae', 'fae_code' => 'CNC-1']);
        $date = Carbon::tomorrow()->format('Y-m-d');

        $appt = Appointment::create([
            'fae_id' => $fae->id,
            'user_name' => $fae->name,
            'appointment_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Need to cancel',
            'status' => 'pending',
        ]);

        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $fae->id, 'monitoring_fae_name' => $fae->name])
            ->post('/calendar/cancel', [
                'appt_id' => $appt->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Appointment cancelled successfully.');

        $this->assertDatabaseHas('appointments', [
            'id' => $appt->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_fae_cannot_cancel_other_fae_appointment(): void
    {
        $faeA = FaeUser::create(['name' => 'Owner Fae', 'fae_code' => 'OWN-1']);
        $faeB = FaeUser::create(['name' => 'Other Fae', 'fae_code' => 'OTH-1']);
        $date = Carbon::tomorrow()->format('Y-m-d');

        $appt = Appointment::create([
            'fae_id' => $faeA->id,
            'user_name' => $faeA->name,
            'appointment_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Owner booking',
            'status' => 'pending',
        ]);

        // FAE B tries to cancel FAE A's appointment
        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $faeB->id, 'monitoring_fae_name' => $faeB->name])
            ->post('/calendar/cancel', [
                'appt_id' => $appt->id,
            ])
            ->assertSessionHas('error', 'You can only cancel your own appointments.');

        $this->assertDatabaseHas('appointments', [
            'id' => $appt->id,
            'status' => 'pending',
        ]);
    }

    public function test_fae_can_rebook_after_cancelling_appointment(): void
    {
        $fae = FaeUser::create(['name' => 'Rebook Fae', 'fae_code' => 'RBK-1']);
        $date = Carbon::tomorrow()->format('Y-m-d');

        $appt = Appointment::create([
            'fae_id' => $fae->id,
            'user_name' => $fae->name,
            'appointment_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Original booking',
            'status' => 'pending',
        ]);

        // Cancel first appointment
        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $fae->id, 'monitoring_fae_name' => $fae->name])
            ->post('/calendar/cancel', ['appt_id' => $appt->id])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', ['id' => $appt->id, 'status' => 'cancelled']);

        // Now book a new appointment on the same date
        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $fae->id, 'monitoring_fae_name' => $fae->name])
            ->post('/calendar/book', [
                'appointment_date' => $date,
                'start_time' => '13:00',
                'end_time' => '15:00',
                'reason' => 'New booking after cancellation',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'fae_id' => $fae->id,
            'reason' => 'New booking after cancellation',
            'status' => 'pending',
        ]);
    }

    public function test_fae_cannot_cancel_past_appointment(): void
    {
        $fae = FaeUser::create(['name' => 'Past Fae', 'fae_code' => 'PST-2']);
        $pastDate = Carbon::yesterday()->format('Y-m-d');

        $appt = Appointment::create([
            'fae_id' => $fae->id,
            'user_name' => $fae->name,
            'appointment_date' => $pastDate,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Past appointment',
            'status' => 'pending',
        ]);

        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $fae->id, 'monitoring_fae_name' => $fae->name])
            ->post('/calendar/cancel', ['appt_id' => $appt->id])
            ->assertSessionHas('error', 'Past appointments cannot be cancelled.');

        $this->assertDatabaseHas('appointments', ['id' => $appt->id, 'status' => 'pending']);
    }

    public function test_fae_can_cancel_rejected_appointment_and_rebook(): void
    {
        $fae = FaeUser::create(['name' => 'Rejected Cancel Fae', 'fae_code' => 'REJ-CNC']);
        $date = Carbon::tomorrow()->format('Y-m-d');

        $appt = Appointment::create([
            'fae_id' => $fae->id,
            'user_name' => $fae->name,
            'appointment_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'First attempt',
            'status' => 'rejected',
            'admin_comment' => 'Busy time',
        ]);

        // FAE cancels their rejected appointment via Month Appointments table
        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $fae->id, 'monitoring_fae_name' => $fae->name])
            ->post('/calendar/cancel', ['appt_id' => $appt->id])
            ->assertRedirect()
            ->assertSessionHas('success', 'Appointment cancelled successfully.');

        $this->assertDatabaseHas('appointments', ['id' => $appt->id, 'status' => 'cancelled']);

        // Now FAE can rebook on that date!
        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $fae->id, 'monitoring_fae_name' => $fae->name])
            ->post('/calendar/book', [
                'appointment_date' => $date,
                'start_time' => '14:00',
                'end_time' => '16:00',
                'reason' => 'Second attempt after cancelling rejected',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', [
            'fae_id' => $fae->id,
            'reason' => 'Second attempt after cancelling rejected',
            'status' => 'pending',
        ]);
    }

    public function test_fae_can_delete_own_cancelled_appointment(): void
    {
        $fae = FaeUser::create(['name' => 'Delete Fae', 'fae_code' => 'DEL-1']);
        $date = Carbon::tomorrow()->format('Y-m-d');

        $appt = Appointment::create([
            'fae_id' => $fae->id,
            'user_name' => $fae->name,
            'appointment_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Cancelled booking to delete',
            'status' => 'cancelled',
        ]);

        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $fae->id, 'monitoring_fae_name' => $fae->name])
            ->post('/calendar/appointment/delete', ['appt_id' => $appt->id])
            ->assertRedirect()
            ->assertSessionHas('success', 'Appointment deleted successfully.');

        $this->assertDatabaseMissing('appointments', ['id' => $appt->id]);
    }

    public function test_fae_cannot_delete_active_uncancelled_appointment(): void
    {
        $fae = FaeUser::create(['name' => 'Active Delete Fae', 'fae_code' => 'DEL-2']);
        $date = Carbon::tomorrow()->format('Y-m-d');

        $appt = Appointment::create([
            'fae_id' => $fae->id,
            'user_name' => $fae->name,
            'appointment_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'reason' => 'Active booking attempt delete',
            'status' => 'pending',
        ]);

        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $fae->id, 'monitoring_fae_name' => $fae->name])
            ->post('/calendar/appointment/delete', ['appt_id' => $appt->id])
            ->assertSessionHas('error', 'Only cancelled or expired appointments can be deleted.');

        $this->assertDatabaseHas('appointments', ['id' => $appt->id, 'status' => 'pending']);
    }
}