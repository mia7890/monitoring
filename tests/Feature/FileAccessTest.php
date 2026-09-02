<?php

namespace Tests\Feature;

use App\Models\FaeUser;
use App\Models\Task;
use App\Models\TaskUpdate;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileAccessTest extends TestCase
{
    public function test_files_route_requires_authentication(): void
    {
        $this->get('/files/uploads/report_1_abc.txt')->assertRedirect('/access');
    }

    public function test_attachment_is_visible_to_owning_fae(): void
    {
        Storage::fake('local');

        $owner = FaeUser::create(['name' => 'Owner Fae', 'fae_code' => 'FILE-1']);
        $task = Task::create([
            'fae_id' => $owner->id,
            'task_name' => 'File task',
            'status' => 'Pending',
            'progress' => 0,
        ]);

        $path = 'uploads/report_' . $task->id . '_test.txt';
        Storage::disk('local')->put($path, 'SECRET-REPORT-CONTENT');

        TaskUpdate::create([
            'task_id' => $task->id,
            'fae_id' => $owner->id,
            'author_role' => 'fae',
            'author_name' => $owner->name,
            'message' => 'Here is my report',
            'attachment' => $path,
        ]);

        $response = $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $owner->id, 'monitoring_fae_name' => $owner->name])
            ->get('/files/' . $path);
        $response->assertOk();
        $this->assertEquals('SECRET-REPORT-CONTENT', file_get_contents($response->baseResponse->getFile()->getPathname()));
    }

    public function test_attachment_is_hidden_from_unrelated_fae(): void
    {
        Storage::fake('local');

        $owner = FaeUser::create(['name' => 'Owner Fae', 'fae_code' => 'FILE-2']);
        $other = FaeUser::create(['name' => 'Other Fae', 'fae_code' => 'FILE-3']);

        $task = Task::create([
            'fae_id' => $owner->id,
            'task_name' => 'Private file task',
            'status' => 'Pending',
            'progress' => 0,
        ]);

        $path = 'uploads/report_' . $task->id . '_secret.txt';
        Storage::disk('local')->put($path, 'CLASSIFIED-CONTENT');

        TaskUpdate::create([
            'task_id' => $task->id,
            'fae_id' => $owner->id,
            'author_role' => 'fae',
            'author_name' => $owner->name,
            'message' => 'Confidential',
            'attachment' => $path,
        ]);

        $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $other->id, 'monitoring_fae_name' => $other->name])
            ->get('/files/' . $path)
            ->assertNotFound();
    }

    public function test_attachment_is_visible_to_admin(): void
    {
        Storage::fake('local');

        $owner = FaeUser::create(['name' => 'Owner Fae', 'fae_code' => 'FILE-4']);
        $task = Task::create([
            'fae_id' => $owner->id,
            'task_name' => 'Admin view task',
            'status' => 'Pending',
            'progress' => 0,
        ]);

        $path = 'uploads/report_' . $task->id . '_admin.txt';
        Storage::disk('local')->put($path, 'ADMIN-CAN-VIEW');

        TaskUpdate::create([
            'task_id' => $task->id,
            'fae_id' => $owner->id,
            'author_role' => 'fae',
            'author_name' => $owner->name,
            'message' => 'For admin eyes',
            'attachment' => $path,
        ]);

        $response = $this->withSession(['monitoring_role' => 'admin'])
            ->get('/files/' . $path);
        $response->assertOk();
        $this->assertEquals('ADMIN-CAN-VIEW', file_get_contents($response->baseResponse->getFile()->getPathname()));
    }

    public function test_profile_images_are_shared_within_the_workspace(): void
    {
        Storage::fake('local');

        $imageOwner = FaeUser::create(['name' => 'Image Owner', 'fae_code' => 'FILE-5']);
        $viewer = FaeUser::create(['name' => 'Viewer Fae', 'fae_code' => 'FILE-6']);

        $path = 'uploads/fae_' . $imageOwner->id . '_avatar.jpg';
        Storage::disk('local')->put($path, 'AVATAR-BYTES');

        $response = $this->withSession(['monitoring_role' => 'fae', 'monitoring_fae_id' => $viewer->id, 'monitoring_fae_name' => $viewer->name])
            ->get('/files/' . $path);
        $response->assertOk();
        $this->assertEquals('AVATAR-BYTES', file_get_contents($response->baseResponse->getFile()->getPathname()));
    }

    public function test_path_traversal_is_rejected(): void
    {
        Storage::fake('local');

        $this->withSession(['monitoring_role' => 'admin'])
            ->get('/files/' . rawurlencode('../.env'))
            ->assertNotFound();
    }
}