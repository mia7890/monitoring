<?php

namespace Tests\Feature;

use App\Models\Department;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class DepartmentManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Department::query()->delete();
        Session::put('monitoring_role', 'admin');
    }

    public function test_admin_can_view_department_page(): void
    {
        Department::create([
            'department_name' => 'Engineering Test View',
            'is_active' => true,
        ]);

        $response = $this->get('/departments');

        $response->assertStatus(200)
            ->assertSee('Department Management');
    }

    public function test_admin_can_create_department(): void
    {
        $response = $this->post('/departments', [
            'department_name' => 'Operations Test Create',
            'is_active' => true,
        ]);

        $response->assertRedirect('/departments');
        $this->assertDatabaseHas('departments', ['department_name' => 'Operations Test Create']);
    }
}
