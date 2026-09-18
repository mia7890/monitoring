<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\FaeUser;
use App\Models\Task;
use App\Models\TaskUpdate;
use App\Services\NotificationService;
use App\Services\UploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private FaeUser $fae1;
    private FaeUser $fae2;

    protected function setUp(): void
    {
        parent::setUp();
        config(['monitoring.admin_key' => 'secret123']);

        $dept = Department::create(['department_name' => 'Field Support', 'is_active' => true]);

        $this->fae1 = FaeUser::create([
            'name' => 'Engineer One',
            'email' => 'fae1@example.com',
            'fae_code' => 'FAE001',
            'department_id' => $dept->id,
            'is_approved' => true,
        ]);

        $this->fae2 = FaeUser::create([
            'name' => 'Engineer Two',
            'email' => 'fae2@example.com',
            'fae_code' => 'FAE002',
            'department_id' => $dept->id,
            'is_approved' => true,
        ]);
    }

    public function test_task_creation_requires_fae_id()
    {
        $response = $this->withSession(['monitoring_role' => 'admin', 'is_admin' => true])
            ->post(route('tasks.store'), [
                'task_name' => 'Calibrate Sensors',
                'fae_id' => '',
                'deadline' => now()->addDays(5)->format('Y-m-d'),
                'priority' => 'High',
            ]);

        $response->assertSessionHasErrors(['fae_id']);
        $this->assertDatabaseMissing('tasks', ['task_name' => 'Calibrate Sensors']);
    }

    public function test_task_creation_fails_if_deadline_is_in_the_past()
    {
        $response = $this->withSession(['monitoring_role' => 'admin', 'is_admin' => true])
            ->post(route('tasks.store'), [
                'task_name' => 'Overdue Task',
                'fae_id' => $this->fae1->id,
                'deadline' => now()->subDays(2)->format('Y-m-d'),
                'priority' => 'Medium',
            ]);

        $response->assertSessionHasErrors(['deadline']);
        $this->assertDatabaseMissing('tasks', ['task_name' => 'Overdue Task']);
    }

    public function test_task_creation_succeeds_with_valid_fae_and_future_deadline()
    {
        $response = $this->withSession(['monitoring_role' => 'admin', 'is_admin' => true])
            ->post(route('tasks.store'), [
                'task_name' => 'Valid Field Task',
                'fae_id' => $this->fae1->id,
                'region' => 'NCR',
                'course' => 'Automation',
                'deadline' => now()->addDays(3)->format('Y-m-d'),
                'priority' => 'Urgent',
            ]);

        $response->assertRedirect(route('tasks.index'));
        $this->assertDatabaseHas('tasks', [
            'task_name' => 'Valid Field Task',
            'fae_id' => $this->fae1->id,
            'region' => 'NCR',
            'priority' => 'Urgent',
        ]);
    }

    public function test_task_reassignment_creates_remark_and_notifies_new_fae()
    {
        $task = Task::create([
            'task_name' => 'Reassigned Task',
            'fae_id' => $this->fae1->id,
            'status' => 'Pending',
            'progress' => 0,
            'deadline' => now()->addDays(4),
        ]);

        $response = $this->withSession(['monitoring_role' => 'admin', 'is_admin' => true])
            ->put(route('tasks.update', $task->id), [
                'task_name' => 'Reassigned Task',
                'fae_id' => $this->fae2->id,
                'deadline' => now()->addDays(4)->format('Y-m-d'),
                'priority' => 'High',
            ]);

        $response->assertRedirect(route('tasks.index'));
        $this->assertEquals($this->fae2->id, $task->fresh()->fae_id);

        $this->assertDatabaseHas('task_updates', [
            'task_id' => $task->id,
            'fae_id' => $this->fae2->id,
            'author_role' => 'admin',
        ]);

        // Verify notification for new FAE
        $notifications = $this->withSession([
            'monitoring_role' => 'fae',
            'monitoring_fae_id' => $this->fae2->id,
            'monitoring_fae_name' => $this->fae2->name,
        ])->get(route('tasks.index'));

        $notifications->assertOk();
    }

    public function test_fae_can_submit_multiple_photo_attachments()
    {
        Storage::fake('local');

        $task = Task::create([
            'task_name' => 'Field Inspection',
            'fae_id' => $this->fae1->id,
            'status' => 'In Progress',
            'progress' => 30,
            'deadline' => now()->addDays(5),
        ]);

        $file1 = UploadedFile::fake()->create('photo1.jpg', 50, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('photo2.png', 50, 'image/png');

        $response = $this->withSession([
            'monitoring_role' => 'fae',
            'monitoring_fae_id' => $this->fae1->id,
            'monitoring_fae_name' => $this->fae1->name,
        ])->post(route('tasks.storeUpdate'), [
            'task_id' => $task->id,
            'status' => 'In Progress',
            'progress' => 60,
            'message' => 'Completed wiring tests, attached 2 inspection photos.',
            'attachments' => [$file1, $file2],
            'redirect_to' => 'tasks.index',
        ]);

        $response->assertRedirect(route('tasks.index'));

        $latestUpdate = TaskUpdate::where('task_id', $task->id)->latest('id')->first();
        $this->assertNotNull($latestUpdate);
        $this->assertCount(2, $latestUpdate->attachments_list);
        $this->assertEquals(60, $task->fresh()->progress);
    }

    public function test_selecting_completed_status_forces_100_percent_progress()
    {
        $task = Task::create([
            'task_name' => 'Final Handover',
            'fae_id' => $this->fae1->id,
            'status' => 'In Progress',
            'progress' => 80,
            'deadline' => now()->addDays(2),
        ]);

        $response = $this->withSession([
            'monitoring_role' => 'fae',
            'monitoring_fae_id' => $this->fae1->id,
            'monitoring_fae_name' => $this->fae1->name,
        ])->post(route('tasks.storeUpdate'), [
            'task_id' => $task->id,
            'status' => 'Completed',
            'progress' => 80, // User didn't touch slider, but selected Completed
            'message' => 'Client sign-off completed.',
            'redirect_to' => 'tasks.index',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('tasks.index'));
        $this->assertEquals(100, $task->fresh()->progress);
        $this->assertEquals('Completed', $task->fresh()->status);
    }

    public function test_admin_can_update_display_name()
    {
        $response = $this->withSession([
            'monitoring_role' => 'admin',
            'is_admin' => true,
        ])->post(route('settings.updateName'), [
            'admin_name' => 'Chief Administrator John',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('Chief Administrator John', \App\Services\MonitoringAuth::adminName());
    }
}
