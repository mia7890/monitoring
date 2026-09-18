<?php

namespace Tests\Feature;

use App\Models\FaeUser;
use App\Models\Task;
use App\Models\TaskUpdate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskUpdateTest extends TestCase
{
    private function makeFae(string $code): FaeUser
    {
        return FaeUser::create([
            'name' => 'FAE ' . $code,
            'fae_code' => $code,
        ]);
    }

    public function test_guest_cannot_submit_task_update(): void
    {
        $this->post('/tasks/store-update', [
            'task_id' => 1,
            'message' => 'Blocked',
        ])->assertRedirect('/access');
    }

    public function test_store_update_rejects_invalid_status(): void
    {
        $fae = $this->makeFae('ST-1');
        $task = Task::create([
            'fae_id' => $fae->id,
            'task_name' => 'Status validation task',
            'status' => 'Pending',
            'progress' => 0,
        ]);

        $this->withSession(['monitoring_role' => 'admin'])
            ->post('/tasks/store-update', [
                'task_id' => $task->id,
                'message' => 'Testing invalid status',
                'status' => 'Bogus-Nonsense',
                'progress' => 10,
            ])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('task_updates', 0);
    }

    public function test_admin_can_submit_valid_update_without_attachment(): void
    {
        $fae = $this->makeFae('ST-2');
        $task = Task::create([
            'fae_id' => $fae->id,
            'task_name' => 'Valid update task',
            'status' => 'Pending',
            'progress' => 0,
        ]);

        $this->withSession(['monitoring_role' => 'admin'])
            ->post('/tasks/store-update', [
                'task_id' => $task->id,
                'message' => 'Marking as complete',
                'status' => 'Completed',
                'progress' => 100,
            ])
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('task_updates', [
            'task_id' => $task->id,
            'author_role' => 'admin',
            'status_at_update' => 'Completed',
            'progress_at_update' => 100,
        ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'Completed',
            'progress' => 100,
        ]);
    }

    public function test_admin_can_submit_update_with_attachment(): void
    {
        Storage::fake('local');

        $fae = $this->makeFae('ST-3');
        $task = Task::create([
            'fae_id' => $fae->id,
            'task_name' => 'Attachment task',
            'status' => 'In Progress',
            'progress' => 50,
        ]);

        $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

        $this->withSession(['monitoring_role' => 'admin'])
            ->post('/tasks/store-update', [
                'task_id' => $task->id,
                'message' => 'Uploading evidence',
                'attachment' => $file,
            ])
            ->assertRedirect(route('tasks.index'));

        $update = TaskUpdate::where('task_id', $task->id)->first();
        $this->assertNotNull($update);
        $this->assertStringStartsWith('uploads/report_', (string)$update->attachment);
        $this->assertTrue(Storage::disk('local')->exists($update->attachment));
    }

    public function test_store_update_rejects_unsupported_attachment_extension(): void
    {
        Storage::fake('local');

        $fae = $this->makeFae('ST-4');
        $task = Task::create([
            'fae_id' => $fae->id,
            'task_name' => 'Bad attachment task',
            'status' => 'Pending',
            'progress' => 0,
        ]);

        $file = UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream');

        $this->withSession(['monitoring_role' => 'admin'])
            ->post('/tasks/store-update', [
                'task_id' => $task->id,
                'message' => 'Should be rejected',
                'attachment' => $file,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('task_updates', 0);
    }

    public function test_fae_cannot_update_another_faes_task(): void
    {
        $owner = $this->makeFae('OWN-1');
        $intruder = $this->makeFae('INT-1');

        $task = Task::create([
            'fae_id' => $owner->id,
            'task_name' => 'Owner task',
            'status' => 'Pending',
            'progress' => 0,
        ]);

        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $intruder->id, 'monitoring_fae_name' => $intruder->name])
            ->post('/tasks/store-update', [
                'task_id' => $task->id,
                'message' => 'Sneaky attempt',
            ])
            ->assertSessionHas('error', 'Task not found or access denied.');

        $this->assertDatabaseCount('task_updates', 0);
    }

    public function test_assigned_fae_can_submit_update(): void
    {
        $owner = $this->makeFae('ASG-1');

        $task = Task::create([
            'fae_id' => $owner->id,
            'task_name' => 'My task',
            'status' => 'Pending',
            'progress' => 0,
        ]);

        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $owner->id, 'monitoring_fae_name' => $owner->name])
            ->post('/tasks/store-update', [
                'task_id' => $task->id,
                'message' => 'Progress update',
                'progress' => 25,
            ])
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('task_updates', [
            'task_id' => $task->id,
            'author_role' => 'fae',
            'author_name' => $owner->name,
            'progress_at_update' => 25,
        ]);
    }

    public function test_admin_can_update_task_details_with_array_fae_id(): void
    {
        $fae1 = $this->makeFae('ARRAY-1');
        $fae2 = $this->makeFae('ARRAY-2');

        $task = Task::create([
            'fae_id' => $fae1->id,
            'task_name' => 'Original Task Name',
            'status' => 'Pending',
            'progress' => 0,
        ]);

        $this->withSession(['monitoring_role' => 'admin'])
            ->put('/tasks/' . $task->id, [
                'task_name' => 'Updated Task Name',
                'fae_id' => [$fae2->id],
                'priority' => 'High',
            ])
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'task_name' => 'Updated Task Name',
            'fae_id' => $fae2->id,
            'priority' => 'High',
        ]);
    }
}