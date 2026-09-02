<?php

namespace Tests\Feature;

use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentDropdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_fae_page_displays_department_dropdown_from_department_table(): void
    {
        Department::create(['department_name' => 'Engineering']);
        Department::create(['department_name' => 'IT Support']);

        $this->withSession(['monitoring_role' => 'admin'])
            ->get('/fae')
            ->assertOk()
            ->assertSeeText('Select Department')
            ->assertSeeText('Engineering')
            ->assertSeeText('IT Support');
    }
}
