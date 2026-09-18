<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\FaeUser;
use App\Models\Task;
use App\Models\TaskUpdate;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskReportAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['monitoring.admin_key' => 'secret123']);
    }

    public function test_admin_can_view_single_task_report(): void
    {
        $department = Department::create(['department_name' => 'Field Operations', 'is_active' => true]);
        $fae = FaeUser::create([
            'name' => 'Alice FAE',
            'email' => 'alice@hytecpower.net',
            'phone' => '09123456789',
            'department_id' => $department->id,
            'is_active' => true,
            'is_approved' => true,
        ]);

        $task = Task::create([
            'fae_id' => $fae->id,
            'task_name' => 'Substation Inspection',
            'status' => 'In Progress',
            'progress' => 45,
            'deadline' => Carbon::now()->addDays(5),
            'priority' => 'High',
        ]);

        $response = $this->withSession([
            'monitoring_role' => 'admin',
        ])->get(route('tasks.report', $task));

        $response->assertStatus(200);
        $response->assertSee('Substation Inspection');
        $response->assertSee('Alice FAE');
        $response->assertSee('OFFICIAL TASK REPORT');
    }

    public function test_fae_can_view_own_task_report_but_not_others(): void
    {
        $fae1 = FaeUser::create([
            'name' => 'FAE One',
            'email' => 'fae1@hytecpower.net',
            'phone' => '09111111111',
            'is_active' => true,
            'is_approved' => true,
        ]);
        $fae2 = FaeUser::create([
            'name' => 'FAE Two',
            'email' => 'fae2@hytecpower.net',
            'phone' => '09222222222',
            'is_active' => true,
            'is_approved' => true,
        ]);

        $task1 = Task::create([
            'fae_id' => $fae1->id,
            'task_name' => 'FAE 1 Task',
            'status' => 'Pending',
            'progress' => 0,
        ]);

        // FAE 1 views own task
        $res1 = $this->withSession([
            'monitoring_role' => 'fae',
            'monitoring_fae_id' => $fae1->id,
            'monitoring_fae_name' => $fae1->name,
        ])->get(route('tasks.report', $task1));

        $res1->assertStatus(200);
        $res1->assertSee('FAE 1 Task');

        // FAE 2 tries to view FAE 1 task -> 403
        $res2 = $this->withSession([
            'monitoring_role' => 'fae',
            'monitoring_fae_id' => $fae2->id,
            'monitoring_fae_name' => $fae2->name,
        ])->get(route('tasks.report', $task1));

        $res2->assertStatus(403);
    }

    public function test_tasks_summary_report_is_accessible(): void
    {
        Task::create([
            'task_name' => 'Global Maintenance',
            'status' => 'In Progress',
            'progress' => 50,
        ]);

        $response = $this->withSession([
            'monitoring_role' => 'admin',
        ])->get(route('tasks.exportReport'));

        $response->assertStatus(200);
        $response->assertSee('EXECUTIVE SUMMARY REPORT');
        $response->assertSee('Global Maintenance');
    }

    public function test_completed_task_rejects_further_fae_updates(): void
    {
        $fae = FaeUser::create([
            'name' => 'Completed Worker',
            'email' => 'worker@hytecpower.net',
            'phone' => '09333333333',
            'is_active' => true,
            'is_approved' => true,
        ]);

        $task = Task::create([
            'fae_id' => $fae->id,
            'task_name' => 'Finalized Calibration',
            'status' => 'Completed',
            'progress' => 100,
        ]);

        $response = $this->withSession([
            'monitoring_role' => 'fae',
            'monitoring_fae_id' => $fae->id,
            'monitoring_fae_name' => $fae->name,
        ])->post(route('tasks.storeUpdate'), [
            'task_id' => $task->id,
            'message' => 'Trying to update completed task',
            'progress' => 100,
        ]);

        $response->assertSessionHas('error');
        $this->assertEquals(0, TaskUpdate::where('task_id', $task->id)->count());
    }

    public function test_notifications_contain_admin_notes_for_fae(): void
    {
        $fae = FaeUser::create([
            'name' => 'Notified FAE',
            'email' => 'notified@hytecpower.net',
            'phone' => '09444444444',
            'is_active' => true,
            'is_approved' => true,
        ]);

        Appointment::create([
            'fae_id' => $fae->id,
            'user_name' => $fae->name,
            'appointment_date' => Carbon::tomorrow(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'reason' => 'Quarterly Evaluation',
            'status' => 'accepted',
            'admin_comment' => 'Please bring your Q3 report drafts.',
        ]);

        session([
            'monitoring_role' => 'fae',
            'monitoring_fae_id' => $fae->id,
            'monitoring_fae_name' => $fae->name,
        ]);

        $notifications = NotificationService::notifications();
        $this->assertNotEmpty($notifications);

        $found = false;
        foreach ($notifications as $n) {
            if (isset($n['admin_notes']) && str_contains($n['admin_notes'], 'Q3 report drafts')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Notification must contain the admin notes comment.');
    }
}
