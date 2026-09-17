<?php

namespace Tests\Feature;

use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentDropdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_displays_department_dropdown_from_department_table(): void
    {
        Department::create(['department_name' => 'Engineering']);
        Department::create(['department_name' => 'IT Support']);

        $this->get('/register')
            ->assertOk()
            ->assertSeeText('Select')
            ->assertSeeText('Engineering')
            ->assertSeeText('IT Support');
    }
}
