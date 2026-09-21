<?php

namespace Tests\Feature;

use App\Models\StaffInvitation;
use App\Models\User;
use App\Notifications\StaffInvited;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    private function invite(User $admin, User $recipient, array $overrides = []): array
    {
        $token = Str::random(64);
        $row = StaffInvitation::create([...['email' => $recipient->email, 'role' => 'staff', 'token_hash' => hash('sha256', $token), 'invited_by' => $admin->id, 'expires_at' => now()->addDays(7)], ...$overrides]);
        return [$row, $token];
    }
    public function test_only_admin_can_invite_and_raw_token_is_not_stored(): void
    {
        Notification::fake();
        $this->asStaff($this->member('staff', true))->post('/beheer/uitnodigingen', ['email' => 'maker@example.test', 'role' => 'staff'])->assertForbidden();
        $admin = $this->member('admin', true);
        $this->asStaff($admin)->post('/beheer/uitnodigingen', ['email' => 'maker@example.test', 'role' => 'admin'])->assertSessionHasErrors('role');
        $this->asStaff($admin)->post('/beheer/uitnodigingen', ['email' => 'maker@example.test', 'role' => 'staff'])->assertRedirect();
        Notification::assertSentOnDemand(StaffInvited::class, function ($notification) {
            $raw = basename(parse_url($notification->acceptUrl, PHP_URL_PATH));
            $this->assertDatabaseHas('staff_invitations', ['token_hash' => hash('sha256', $raw)]);
            $this->assertDatabaseMissing('staff_invitations', ['token_hash' => $raw]);
            return strlen($raw) === 64;
        });
    }
    public function test_correct_verified_user_can_accept_only_once(): void
    {
        $admin = $this->member('admin', true); $recipient = $this->member();
        [$row, $token] = $this->invite($admin, $recipient);
        $this->actingAs($recipient)->get('/uitnodiging/'.$token)->assertRedirect('/uitnodiging');
        $this->post('/uitnodiging')->assertRedirect('/settings/security');
        $this->assertTrue($recipient->fresh()->hasRole('staff'));
        $this->assertFalse($recipient->fresh()->can('staff.invite'));
        $this->assertNotNull($row->fresh()->accepted_at);
        $this->assertFalse($recipient->fresh()->professionalProfile->is_public);
        $this->get('/uitnodiging/'.$token)->assertStatus(410);
    }
    public function test_wrong_email_cannot_claim_invitation(): void
    {
        $admin = $this->member('admin', true); $recipient = $this->member(); $stranger = $this->member();
        [$row, $token] = $this->invite($admin, $recipient);
        $this->actingAs($stranger)->get('/uitnodiging/'.$token);
        $this->post('/uitnodiging')->assertSessionHasErrors('invitation');
        $this->assertNull($row->fresh()->accepted_at);
        $this->assertFalse($stranger->fresh()->hasRole('staff'));
    }
    public function test_unverified_user_must_verify_first(): void
    {
        $recipient = User::factory()->unverified()->create(); $recipient->assignRole('member');
        [, $token] = $this->invite($this->member('admin', true), $recipient);
        $this->actingAs($recipient)->get('/uitnodiging/'.$token);
        $this->post('/uitnodiging')->assertRedirect('/email/verify');
    }
    public function test_expired_revoked_and_former_admin_invitations_fail(): void
    {
        $admin = $this->member('admin', true); $recipient = $this->member();
        [, $expired] = $this->invite($admin, $recipient, ['expires_at' => now()->subSecond()]);
        [, $revoked] = $this->invite($admin, $recipient, ['revoked_at' => now()]);
        [, $pending] = $this->invite($admin, $recipient);
        $this->get('/uitnodiging/'.$expired)->assertStatus(410);
        $this->get('/uitnodiging/'.$revoked)->assertStatus(410);
        $admin->removeRole('admin');
        $this->get('/uitnodiging/'.$pending)->assertStatus(410);
    }
    public function test_revocation_is_checked_again_at_acceptance(): void
    {
        $admin = $this->member('admin', true); $recipient = $this->member();
        [$row, $token] = $this->invite($admin, $recipient);
        $this->actingAs($recipient)->get('/uitnodiging/'.$token);
        $row->update(['revoked_at' => now()]);
        $this->post('/uitnodiging')->assertSessionHasErrors('invitation');
    }
    public function test_admin_can_revoke_staff_but_not_another_admin(): void
    {
        $admin = $this->member('admin', true); $staff = $this->member('staff', true);
        $this->asStaff($admin)->delete('/beheer/medewerkers/'.$staff->id)->assertRedirect();
        $this->assertFalse($staff->fresh()->hasRole('staff'));
        $this->delete('/beheer/medewerkers/'.$admin->id)->assertForbidden();
    }
}
