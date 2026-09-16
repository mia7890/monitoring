<?php

namespace Tests\Feature;

use App\Models\FaeUser;
use App\Services\MonitoringAuth;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_guest_is_redirected_to_access_for_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/access');
    }

    public function test_guest_is_redirected_from_admin_route(): void
    {
        $this->get('/departments')->assertRedirect('/access');
    }

    public function test_admin_login_with_valid_key_logs_in_directly(): void
    {
        $response = $this->post('/login', [
            'access_type' => 'admin',
            'admin_key' => config('monitoring.admin_key'),
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertEquals('admin', MonitoringAuth::role());
        $this->assertTrue(MonitoringAuth::isAdmin());
    }

    public function test_admin_login_with_invalid_key_is_rejected(): void
    {
        $response = $this->post('/login', [
            'access_type' => 'admin',
            'admin_key' => 'wrong-key',
        ]);

        $response->assertRedirect();
        $this->assertNull(MonitoringAuth::role());
        $response->assertSessionHas('error', 'The admin access key is not valid.');
    }

    public function test_admin_login_is_rejected_when_key_not_configured(): void
    {
        config()->set('monitoring.admin_key', null);

        $response = $this->post('/login', [
            'access_type' => 'admin',
            'admin_key' => 'any-key',
        ]);

        $response->assertRedirect();
        $this->assertNull(MonitoringAuth::role());
        $response->assertSessionHas('error', 'The administrator workspace is not configured. Contact your administrator.');
    }

    public function test_fae_login_with_valid_code_creates_fae_session(): void
    {
        $fae = FaeUser::create([
            'name' => 'Jane Field',
            'fae_code' => 'FAE-2026',
        ]);

        $response = $this->post('/login', [
            'access_type' => 'fae',
            'fae_code' => 'fae-2026',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertTrue(MonitoringAuth::isFae());
        $this->assertEquals($fae->id, MonitoringAuth::faeId());
    }

    public function test_fae_login_with_unknown_code_is_rejected(): void
    {
        $response = $this->post('/login', [
            'access_type' => 'fae',
            'fae_code' => 'DOES-NOT-EXIST',
        ]);

        $response->assertRedirect();
        $this->assertNull(MonitoringAuth::role());
        $response->assertSessionHas('error', 'That FAE code was not found.');
    }

    public function test_direct_fae_link_logs_user_in(): void
    {
        $fae = FaeUser::create([
            'name' => 'Link User',
            'fae_code' => 'LNK-44',
        ]);

        $response = $this->get('/fae-link/lnk-44');

        $response->assertRedirect('/dashboard');
        $this->assertEquals($fae->id, Session::get('monitoring_fae_id'));
    }

    public function test_direct_fae_link_with_unknown_code_shows_error(): void
    {
        $this->get('/fae-link/NOPE')
            ->assertRedirect('/access')
            ->assertSessionHas('error');
    }

    public function test_logout_clears_session(): void
    {
        $this->withSession(['monitoring_role' => 'admin'])
            ->get('/logout')
            ->assertRedirect('/access');

        $this->assertNull(MonitoringAuth::role());
    }
}