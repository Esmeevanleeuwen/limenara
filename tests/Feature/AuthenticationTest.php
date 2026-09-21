<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_public_pages_and_auth_forms_render(): void
    {
        foreach (['/', '/programmas', '/makers', '/privacy', '/login', '/register', '/forgot-password'] as $path) $this->get($path)->assertOk();
    }
    public function test_registration_never_grants_submitted_privileges(): void
    {
        Notification::fake();
        $password = bin2hex(random_bytes(24));
        $this->post('/register', ['name' => 'Testgebruiker', 'email' => 'TEST@example.test', 'password' => $password, 'password_confirmation' => $password, 'role' => 'admin', 'permissions' => ['staff.invite'], 'email_verified_at' => now()->toISOString()])->assertRedirect('/dashboard');
        $user = User::where('email', 'test@example.test')->firstOrFail();
        $this->assertTrue($user->hasRole('member'));
        $this->assertFalse($user->can('staff.invite'));
        $this->assertNull($user->email_verified_at);
        $this->get('/dashboard')->assertRedirect('/email/verify');
    }
    public function test_verification_requires_a_signed_link(): void
    {
        $user = User::factory()->unverified()->create(); $user->assignRole('member');
        $path = '/email/verify/'.$user->id.'/'.sha1($user->email);
        $this->actingAs($user)->get($path)->assertForbidden();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(20), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url)->assertRedirect();
        $this->assertNotNull($user->fresh()->email_verified_at);
    }
    public function test_login_and_logout_work(): void
    {
        $user = $this->member();
        $password = bin2hex(random_bytes(24)); $user->password = $password; $user->save();
        $this->post('/login', ['email' => $user->email, 'password' => $password])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }
    public function test_guests_and_members_cannot_open_employee_tools(): void
    {
        $this->get('/werk/programmas')->assertRedirect('/login');
        $this->actingAs($this->member())->get('/werk/programmas')->assertForbidden();
        $this->get('/beheer/medewerkers')->assertForbidden();
    }
    public function test_staff_needs_configured_and_session_verified_mfa(): void
    {
        $staff = $this->member('staff');
        $this->actingAs($staff)->get('/werk/profiel')->assertRedirect('/settings/security');
        $staff->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_secret' => encrypt(app(\Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider::class)->generateSecretKey())])->save();
        $this->get('/werk/profiel')->assertRedirect('/settings/security');
        $this->asStaff($staff)->get('/werk/profiel')->assertOk();
        $this->withSession(['staff_mfa_at' => now()->subHours(5)->timestamp])->get('/werk/profiel')->assertRedirect('/settings/security');
    }
    public function test_security_secrets_are_not_shared_without_password_reconfirmation(): void
    {
        $staff = $this->member('staff', true);
        $secret = decrypt($staff->two_factor_secret);
        $recovery = bin2hex(random_bytes(16));
        $staff->forceFill(['two_factor_recovery_codes' => encrypt(json_encode([$recovery]))])->save();
        $this->actingAs($staff)->get('/settings/security')->assertOk()->assertDontSee($recovery);
        $this->get('/dashboard')->assertDontSee($secret)->assertDontSee($recovery);
    }
    public function test_user_input_is_escaped_and_responses_are_not_cached(): void
    {
        $user = $this->member(); $user->update(['name' => '<script>alert(1)</script>']);
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }
    public function test_inertia_history_is_encrypted_and_cleared_after_logout(): void
    {
        $this->actingAs($this->member())->get('/dashboard', ['X-Inertia' => 'true'])
            ->assertOk()->assertJsonPath('encryptHistory', true);
        $this->post('/logout')->assertRedirect('/');
        $this->get('/', ['X-Inertia' => 'true'])->assertOk()
            ->assertJsonPath('encryptHistory', true)->assertJsonPath('clearHistory', true);
    }
}
