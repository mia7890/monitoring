<?php

namespace Tests\Feature;

use App\Models\AdminEvent;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\FaeUser;
use App\Models\Task;
use Carbon\Carbon;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_guest_can_view_landing_page_with_calendar_and_login_links(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('Hytec Power Inc.')
            ->assertSee('Schedule & Monitoring Portal')
            ->assertSee('Open Workspace Portal')
            ->assertSee('SCHEDULE GUIDE AND STATUS')
            ->assertSee('CALENDAR SCHEDULE')
            ->assertSee('UPCOMING EVENT');
    }

    public function test_landing_page_displays_scheduled_events_and_tasks(): void
    {
        $currentMonth = date('n');
        $currentYear = date('Y');
        $today = Carbon::today();

        AdminEvent::create([
            'title' => 'Test Calibration Meeting',
            'event_date' => $today->format('Y-m-d'),
            'category' => 'meeting',
        ]);

        Task::create([
            'task_name' => 'Landing Page Audit Task',
            'deadline' => $today->format('Y-m-d'),
            'status' => 'In Progress',
            'progress' => 50,
        ]);

        $response = $this->get("/?month={$currentMonth}&year={$currentYear}");

        $response->assertStatus(200)
            ->assertSee('Test Calibration Meeting')
            ->assertSee('Landing Page Audit Task');
    }

    public function test_authenticated_user_sees_dashboard_link_on_landing_page(): void
    {
        $response = $this->withSession(['monitoring_role' => 'admin'])
            ->get('/');

        $response->assertStatus(200)
            ->assertSee('Go to Dashboard')
            ->assertSee('Sign out');
    }

    public function test_access_page_includes_back_to_home_button(): void
    {
        $response = $this->get('/access');

        $response->assertStatus(200)
            ->assertSee('Back to Home')
            ->assertSee(route('landing'));
    }
}
