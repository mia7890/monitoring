<?php

namespace Tests\Feature;

use App\Mail\AdminKeyMail;
use App\Mail\TestSmtpMail;
use App\Models\Department;
use App\Models\FaeUser;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SmtpMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('admin_key', 'AdminTest123!');
        Setting::set('admin_email', 'admin@hytecpower.com');
    }

    public function test_forgot_admin_key_sends_email_via_smtp(): void
    {
        Mail::fake();

        $response = $this->post(route('forgot.admin.key'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSent(AdminKeyMail::class, function ($mail) {
            return $mail->hasTo('admin@hytecpower.com') && $mail->adminKey === 'AdminTest123!';
        });
    }

    public function test_forgot_admin_key_get_request_redirects_to_access(): void
    {
        $response = $this->get('/forgot-admin-key');
        $response->assertRedirect(route('access'));
    }

    public function test_admin_can_send_test_smtp_email_from_settings(): void
    {
        Mail::fake();
        $this->withSession(['monitoring_role' => 'admin']);

        $response = $this->post(route('settings.testSmtp'), [
            'recipient' => 'testrecipient@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Mail::assertSent(TestSmtpMail::class, function ($mail) {
            return $mail->hasTo('testrecipient@example.com');
        });
    }

    public function test_admin_and_fae_authentication_works_without_google(): void
    {
        // Admin Key Login
        $adminResp = $this->post(route('login'), [
            'access_type' => 'admin',
            'admin_key' => 'AdminTest123!',
        ]);
        $adminResp->assertRedirect(route('dashboard'));
        $this->assertEquals('admin', session('monitoring_role'));

        // FAE Code Login
        $dept = Department::create(['department_name' => 'Field Support']);
        $fae = FaeUser::create([
            'name' => 'John Field FAE',
            'fae_code' => 'FAE-9000',
            'email' => 'john@hytecpower.com',
            'department_id' => $dept->id,
        ]);

        $faeResp = $this->post(route('login'), [
            'access_type' => 'fae',
            'fae_code' => 'FAE-9000',
        ]);
        $faeResp->assertRedirect(route('dashboard'));
        $this->assertEquals('fae', session('monitoring_role'));
        $this->assertEquals($fae->id, session('monitoring_fae_id'));
    }
}
