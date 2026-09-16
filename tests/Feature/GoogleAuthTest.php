<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\FaeUser;
use App\Models\Setting;
use App\Models\Task;
use App\Services\GoogleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('admin_key', 'AdminTest123!');
        Setting::set('google_client_id', 'test-client-id.apps.googleusercontent.com');
        Setting::set('google_client_secret', 'test-client-secret');
        Setting::set('google_redirect_uri', 'http://localhost/monitoring/public/auth/google/callback');
    }

    public function test_redirect_requires_google_credentials_configured(): void
    {
        Setting::set('google_client_id', '');
        Setting::set('google_client_secret', '');
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $response = $this->get(route('google.redirect'));
        $response->assertRedirect(route('access'));
        $response->assertSessionHas('error');
    }

    public function test_guest_redirect_to_google_is_rejected_to_access(): void
    {
        $response = $this->get(route('google.redirect'));
        $response->assertRedirect(route('access'));
        $response->assertSessionHas('error');
    }

    public function test_authenticated_admin_redirect_generates_valid_google_oauth_url(): void
    {
        $this->withSession(['monitoring_role' => 'admin']);

        $response = $this->get(route('google.redirect'));
        $response->assertRedirect();
        
        $targetUrl = $response->headers->get('Location');
        $this->assertStringContainsString('accounts.google.com', $targetUrl);
        $this->assertStringContainsString('client_id=test-client-id.apps.googleusercontent.com', $targetUrl);
        $this->assertStringContainsString('scope=', $targetUrl);
        $this->assertStringContainsString('state=', $targetUrl);
    }


    public function test_admin_can_update_google_credentials_in_settings(): void
    {
        $this->withSession(['monitoring_role' => 'admin']);

        $response = $this->post(route('settings.updateGoogleCredentials'), [
            'google_client_id' => 'new-google-id.apps.googleusercontent.com',
            'google_client_secret' => 'new-google-secret',
            'google_redirect_uri' => 'http://localhost/monitoring/public/auth/google/callback',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('new-google-id.apps.googleusercontent.com', Setting::get('google_client_id'));
        $this->assertEquals('new-google-secret', Setting::get('google_client_secret'));
    }

    public function test_google_callback_with_error_redirects_with_message(): void
    {
        $response = $this->get(route('google.callback', ['error' => 'access_denied']));
        $response->assertRedirect(route('access'));
        $response->assertSessionHas('error');
    }

    public function test_google_callback_connects_admin_account(): void
    {
        $this->withSession(['monitoring_role' => 'admin']);

        Http::fake([
            GoogleService::GOOGLE_TOKEN_ENDPOINT => Http::response([
                'access_token' => 'mock-admin-access-token',
                'refresh_token' => 'mock-admin-refresh-token',
                'expires_in' => 3600,
            ], 200),
            GoogleService::GOOGLE_USERINFO_ENDPOINT => Http::response([
                'sub' => 'google-admin-12345',
                'email' => 'admin@hytecpower.com',
                'name' => 'Admin User',
                'picture' => 'https://lh3.googleusercontent.com/photo.jpg',
            ], 200),
        ]);

        $state = base64_encode(json_encode(['mode' => 'connect_admin']));
        $response = $this->get(route('google.callback', ['code' => 'valid-mock-code', 'state' => $state]));

        $response->assertRedirect(route('settings.index'));
        $response->assertSessionHas('success');
        $this->assertEquals('admin@hytecpower.com', Setting::get('admin_google_email'));
    }


    public function test_google_callback_connects_account_for_logged_in_fae(): void
    {
        $dept = Department::create(['department_name' => 'Support']);
        $fae = FaeUser::create([
            'name' => 'Alex Support',
            'fae_code' => 'FAE-777',
            'email' => 'alex@hytecpower.com',
            'department_id' => $dept->id,
        ]);

        $this->withSession([
            'monitoring_role' => 'fae',
            'monitoring_fae_id' => $fae->id,
            'monitoring_fae_name' => $fae->name,
            'monitoring_fae_code' => $fae->fae_code,
        ]);

        Http::fake([
            GoogleService::GOOGLE_TOKEN_ENDPOINT => Http::response([
                'access_token' => 'mock-connect-token',
                'refresh_token' => 'mock-connect-refresh',
                'expires_in' => 3600,
            ], 200),
            GoogleService::GOOGLE_USERINFO_ENDPOINT => Http::response([
                'sub' => 'google-alex-777',
                'email' => 'alex.personal@gmail.com',
                'name' => 'Alex Personal',
                'picture' => 'https://lh3.googleusercontent.com/alex.jpg',
            ], 200),
        ]);

        $state = base64_encode(json_encode(['mode' => 'connect_fae']));
        $response = $this->get(route('google.callback', ['code' => 'mock-code', 'state' => $state]));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $fae->refresh();
        $this->assertEquals('google-alex-777', $fae->google_id);
        $this->assertEquals('alex.personal@gmail.com', $fae->google_email);
    }

    public function test_fae_can_disconnect_google_account(): void
    {
        $dept = Department::create(['department_name' => 'Support']);
        $fae = FaeUser::create([
            'name' => 'Alex Support',
            'fae_code' => 'FAE-777',
            'email' => 'alex@hytecpower.com',
            'google_id' => 'google-alex-777',
            'google_email' => 'alex@gmail.com',
            'google_access_token' => 'some-token',
            'department_id' => $dept->id,
        ]);

        $this->withSession([
            'monitoring_role' => 'fae',
            'monitoring_fae_id' => $fae->id,
            'monitoring_fae_name' => $fae->name,
            'monitoring_fae_code' => $fae->fae_code,
        ]);

        $response = $this->post(route('google.disconnect'));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fae->refresh();
        $this->assertNull($fae->google_id);
        $this->assertNull($fae->google_access_token);
    }

    public function test_unauthenticated_google_callback_redirects_with_error(): void
    {
        Http::fake([
            GoogleService::GOOGLE_TOKEN_ENDPOINT => Http::response([
                'access_token' => 'mock-token',
                'refresh_token' => 'mock-refresh',
                'expires_in' => 3600,
            ], 200),
            GoogleService::GOOGLE_USERINFO_ENDPOINT => Http::response([
                'sub' => 'google-unknown-999',
                'email' => 'unknown.stranger@gmail.com',
                'name' => 'Unknown Person',
            ], 200),
        ]);

        $state = base64_encode(json_encode(['mode' => 'connect_fae']));
        $response = $this->get(route('google.callback', ['code' => 'mock-code', 'state' => $state]));

        $response->assertRedirect(route('access'));
        $response->assertSessionHas('error');
    }


    public function test_calendar_sync_via_google_calendar_api(): void
    {
        $dept = Department::create(['department_name' => 'Support']);
        $fae = FaeUser::create([
            'name' => 'Alex Support',
            'fae_code' => 'FAE-777',
            'email' => 'alex@hytecpower.com',
            'google_id' => 'google-alex-777',
            'google_email' => 'alex@gmail.com',
            'google_access_token' => 'valid-access-token',
            'google_token_expires_at' => Carbon::now()->addHour()->toDateTimeString(),
            'department_id' => $dept->id,
        ]);

        $task = Task::create([
            'fae_id' => $fae->id,
            'task_name' => 'Client Site Inspection',
            'region' => 'NCR',
            'deadline' => Carbon::now()->addDays(3)->toDateString(),
            'status' => 'Pending',
            'progress' => 0,
        ]);

        $this->withSession([
            'monitoring_role' => 'fae',
            'monitoring_fae_id' => $fae->id,
            'monitoring_fae_name' => $fae->name,
            'monitoring_fae_code' => $fae->fae_code,
        ]);

        Http::fake([
            GoogleService::GOOGLE_CALENDAR_ENDPOINT => Http::response([
                'id' => 'mock-calendar-event-id-123',
                'htmlLink' => 'https://www.google.com/calendar/event?eid=mock123',
            ], 200),
        ]);

        $response = $this->post(route('google.syncCalendar'), [
            'type' => 'task',
            'id' => $task->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_google_drive_upload_for_authenticated_fae(): void
    {
        $dept = Department::create(['department_name' => 'Support']);
        $fae = FaeUser::create([
            'name' => 'Alex Support',
            'fae_code' => 'FAE-777',
            'email' => 'alex@hytecpower.com',
            'google_id' => 'google-alex-777',
            'google_email' => 'alex@gmail.com',
            'google_access_token' => 'valid-access-token',
            'google_token_expires_at' => Carbon::now()->addHour()->toDateTimeString(),
            'department_id' => $dept->id,
        ]);

        $this->withSession([
            'monitoring_role' => 'fae',
            'monitoring_fae_id' => $fae->id,
            'monitoring_fae_name' => $fae->name,
            'monitoring_fae_code' => $fae->fae_code,
        ]);

        Http::fake([
            'https://www.googleapis.com/upload/drive/v3/files*' => Http::response([
                'id' => 'drive-file-abc123xyz',
                'name' => 'inspection_report.pdf',
                'webViewLink' => 'https://drive.google.com/file/d/drive-file-abc123xyz/view',
            ], 200),
        ]);

        $fakeFile = \Illuminate\Http\UploadedFile::fake()->create('inspection_report.pdf', 100, 'application/pdf');

        $response = $this->post(route('google.uploadDrive'), [
            'file' => $fakeFile,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_calendar_page_displays_google_workspace_tools(): void
    {
        $this->withSession(['monitoring_role' => 'admin']);

        $response = $this->get(route('calendar.index'));
        $response->assertStatus(200);
        $response->assertSee('Google Workspace');
        $response->assertSee('Calendar');
        $response->assertSee('Drive');
    }
}
