<?php

namespace Tests\Feature;

use App\Mail\ContactApprovedMail;
use App\Models\Department;
use App\Models\FaeUser;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('admin_key', 'AdminTest123!');
        Setting::set('admin_email', 'admin@hytecpower.com');
    }

    public function test_registration_page_is_accessible(): void
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);
        $response->assertSee('Create Account');
        $response->assertSee('Admin Approval');
    }

    public function test_user_can_submit_registration_request(): void
    {
        $dept = Department::create(['department_name' => 'Field Support']);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Maria Santos',
            'email' => 'maria.santos@example.com',
            'phone' => '09181234567',
            'department_id' => $dept->id,
        ]);

        $response->assertRedirect(route('access'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('fae_users', [
            'name' => 'Maria Santos',
            'email' => 'maria.santos@example.com',
            'phone' => '09181234567',
            'department_id' => $dept->id,
            'status' => 'pending',
        ]);
    }

    public function test_registration_fails_if_required_fields_missing(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => '',
            'email' => '',
            'phone' => '',
            'department_id' => '',
        ]);

        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors(['name', 'email', 'phone', 'department_id']);
    }

    public function test_pending_user_cannot_login(): void
    {
        $pendingUser = FaeUser::create([
            'name' => 'Pending Guy',
            'email' => 'pending@example.com',
            'fae_code' => 'TEMP-PEND1',
            'status' => 'pending',
        ]);

        $response = $this->from(route('access'))->post(route('login'), [
            'access_type' => 'fae',
            'fae_code' => 'TEMP-PEND1',
        ]);

        $response->assertRedirect(route('access'));
        $response->assertSessionHas('error');
        $this->assertNull(session('monitoring_role'));
    }

    public function test_admin_can_view_and_approve_pending_registration(): void
    {
        Mail::fake();

        $pendingUser = FaeUser::create([
            'name' => 'Carlos Dizon',
            'email' => 'carlos@example.com',
            'phone' => '09199998888',
            'status' => 'pending',
        ]);

        // Admin views contacts page
        $adminView = $this->withSession(['monitoring_role' => 'admin'])->get(route('fae.index'));
        $adminView->assertStatus(200);
        $adminView->assertSee('Carlos Dizon');
        $adminView->assertSee('Approve &amp; Send Code', false);

        // Admin approves registration
        $approveResp = $this->withSession(['monitoring_role' => 'admin'])
            ->post(route('fae.approve', $pendingUser->id));

        $approveResp->assertRedirect(route('fae.index'));
        $approveResp->assertSessionHas('success');

        $pendingUser->refresh();
        $this->assertEquals('approved', $pendingUser->status);
        $this->assertNotEmpty($pendingUser->fae_code);
        $this->assertStringStartsWith('CTC-', $pendingUser->fae_code);

        // Verify email was sent with the generated code
        Mail::assertSent(ContactApprovedMail::class, function ($mail) use ($pendingUser) {
            return $mail->hasTo('carlos@example.com') &&
                   $mail->contactName === 'Carlos Dizon' &&
                   $mail->accessCode === $pendingUser->fae_code;
        });

        // Approved user can now log in
        $loginResp = $this->from(route('access'))->post(route('login'), [
            'access_type' => 'fae',
            'fae_code' => $pendingUser->fae_code,
        ]);
        $loginResp->assertRedirect(route('dashboard'));
        $this->assertEquals('fae', session('monitoring_role'));
        $this->assertEquals($pendingUser->id, session('monitoring_fae_id'));
    }

    public function test_admin_can_reject_registration(): void
    {
        $pendingUser = FaeUser::create([
            'name' => 'Spam Applicant',
            'email' => 'spam@example.com',
            'status' => 'pending',
        ]);

        $rejectResp = $this->withSession(['monitoring_role' => 'admin'])
            ->post(route('fae.reject', $pendingUser->id));

        $rejectResp->assertRedirect(route('fae.index'));
        $pendingUser->refresh();
        $this->assertEquals('rejected', $pendingUser->status);

        // Rejected user cannot log in
        $this->flushSession();
        $loginResp = $this->from(route('access'))->post(route('login'), [
            'access_type' => 'fae',
            'fae_code' => $pendingUser->fae_code ?: 'NOCODE',
        ]);
        $loginResp->assertRedirect(route('access'));
        $this->assertNull(session('monitoring_role'));
    }
}
