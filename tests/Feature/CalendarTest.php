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
            'reason' => 'Test appointment',
        ])->assertRedirect('/access');
    }

    public function test_fae_can_book_appointment(): void
    {
        $fae = FaeUser::create([
            'name' => 'Booking Fae',
            'fae_code' => 'BKG-1',
        ]);

        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $fae->id, 'monitoring_fae_name' => $fae->name])
            ->post('/calendar/book', [
                'appointment_date' => Carbon::tomorrow()->format('Y-m-d'),
                'reason' => 'Home visit',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'fae_id' => $fae->id,
            'reason' => 'Home visit',
        ]);
    }

    public function test_admin_cannot_book_appointment(): void
    {
        $this->withSession(['monitoring_role' => 'admin'])
            ->post('/calendar/book', [
                'appointment_date' => Carbon::tomorrow()->format('Y-m-d'),
                'reason' => 'Admin request',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Admin users cannot request appointments.');
    }

    public function test_fae_cannot_book_date_that_is_already_booked(): void
    {
        $faeA = FaeUser::create(['name' => 'Fae A', 'fae_code' => 'BKG-A']);
        FaeUser::create(['name' => 'Fae B', 'fae_code' => 'BKG-B']);

        $date = Carbon::tomorrow()->format('Y-m-d');

        Appointment::create([
            'fae_id' => $faeA->id,
            'user_name' => 'Fae A',
            'appointment_date' => $date,
            'reason' => 'Already booked',
            'status' => 'pending',
        ]);

        $this->assertDatabaseCount('appointments', 1);
        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $faeA->id, 'monitoring_fae_name' => 'Fae B'])
            ->post('/calendar/book', [
                'appointment_date' => $date,
                'reason' => 'Conflict',
            ])
            ->assertSessionHas('error', 'This date is already booked! Please select another date.');
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
                'reason' => 'Should fail',
            ])
            ->assertSessionHas('error', 'The admin is unavailable on this date. Please choose another date.');
    }

    public function test_admin_accepting_appointment_rejects_other_pending_requests_same_day(): void
    {
        $faeA = FaeUser::create(['name' => 'Fae A', 'fae_code' => 'ACC-A']);
        $faeB = FaeUser::create(['name' => 'Fae B', 'fae_code' => 'ACC-B']);

        $date = Carbon::tomorrow()->format('Y-m-d');

        $apptA = Appointment::create([
            'fae_id' => $faeA->id,
            'user_name' => 'Fae A',
            'appointment_date' => $date,
            'reason' => 'First request',
            'status' => 'pending',
        ]);

        Appointment::create([
            'fae_id' => $faeB->id,
            'user_name' => 'Fae B',
            'appointment_date' => $date,
            'reason' => 'Second request',
            'status' => 'pending',
        ]);

        $this->withSession(['monitoring_role' => 'admin'])
            ->post('/calendar/appointment-status', [
                'appt_id' => $apptA->id,
                'status' => 'accepted',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', ['id' => $apptA->id, 'status' => 'accepted']);
        $this->assertDatabaseMissing('appointments', ['status' => 'pending', 'appointment_date' => $date]);
    }
}