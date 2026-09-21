<?php

namespace Tests\Feature;

use App\Models\FaeUser;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TaskAttachmentsAndLinksTest extends TestCase
{
    use RefreshDatabase;

    private function makeFae(string $code = 'TST-01'): FaeUser
    {
        return FaeUser::create([
            'name' => 'FAE ' . $code,
            'fae_code' => $code,
            'status' => 'active',
            'is_approved' => 1,
        ]);
    }

    public function test_admin_can_create_task_with_links_and_attachments(): void
    {
        Storage::fake('local');

        $fae = $this->makeFae('FAE-ATT1');
        $image = UploadedFile::fake()->create('schematic.jpg', 100, 'image/jpeg');

        $response = $this->withSession(['monitoring_role' => 'admin'])
            ->post(route('tasks.store'), [
                'fae_id' => [$fae->id],
                'task_name' => 'Wiring Inspection',
                'region' => 'NCR',
                'course' => 'Robotics',
                'deadline' => now()->addDays(5)->format('Y-m-d'),
                'priority' => 'High',
                'description' => 'Inspect motor wiring and refer to attached schematic diagram.',
                'links' => "https://drive.google.com/test-folder\nhttps://example.com/manual.pdf",
                'attachments' => [$image],
            ]);

        $response->assertRedirect(route('tasks.index'));
        $response->assertSessionHas('success');

        $task = Task::where('task_name', 'Wiring Inspection')->first();
        $this->assertNotNull($task);
        $this->assertEquals('Wiring Inspection', $task->task_name);
        $this->assertCount(2, $task->links_list);
        $this->assertContains('https://drive.google.com/test-folder', $task->links_list);
        $this->assertCount(1, $task->attachments_list);

        $attachmentPath = $task->attachments_list[0];
        Storage::disk('local')->assertExists($attachmentPath);
    }

    public function test_admin_can_update_task_links_and_attachments(): void
    {
        Storage::fake('local');

        $fae = $this->makeFae('FAE-ATT2');
        $task = Task::create([
            'fae_id' => $fae->id,
            'task_name' => 'Original Task',
            'links' => 'https://original.com',
            'deadline' => now()->addDays(4)->format('Y-m-d'),
            'priority' => 'Medium',
        ]);

        $newImage = UploadedFile::fake()->create('updated_diagram.png', 100, 'image/png');

        $response = $this->withSession(['monitoring_role' => 'admin'])
            ->put(route('tasks.update', $task->id), [
                'fae_id' => $fae->id,
                'task_name' => 'Updated Task Name',
                'links' => 'https://updated.com/docs',
                'attachments' => [$newImage],
                'deadline' => now()->addDays(5)->format('Y-m-d'),
                'priority' => 'Urgent',
            ]);

        $response->assertRedirect(route('tasks.index'));

        $task->refresh();
        $this->assertEquals('Updated Task Name', $task->task_name);
        $this->assertEquals(['https://updated.com/docs'], $task->links_list);
        $this->assertCount(1, $task->attachments_list);
    }
}
