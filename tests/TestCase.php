<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    protected $seed = true;
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }
    protected function member(string $role = 'member', bool $mfa = false): User
    {
        $user = User::factory()->create($mfa ? ['two_factor_secret' => encrypt('JBSWY3DPEHPK3PXP'), 'two_factor_confirmed_at' => now()] : []);
        $user->assignRole($role);
        return $user;
    }
    protected function asStaff(User $user): static
    {
        return $this->actingAs($user)->withSession(['staff_mfa_user' => $user->id, 'staff_mfa_at' => now()->timestamp]);
    }
    protected function programData(string $title = 'Een testprogramma'): array
    {
        return ['title' => $title, 'summary' => 'Uitsluitend testinhoud.', 'goals' => 'De programmabouwer testen.', 'estimated_minutes' => 10, 'lessons' => [['title' => 'Kennismaken', 'type' => 'text', 'body' => 'Een testles zonder medisch advies.']]];
    }
}
