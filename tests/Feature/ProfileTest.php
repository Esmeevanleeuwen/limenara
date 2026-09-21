<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function test_profile_is_private_until_opt_in_and_cannot_verify_itself(): void
    {
        $staff = $this->member('staff', true);
        $data = ['display_name' => 'Maker A', 'headline' => 'Een eigen werkwijze', 'biography' => 'Introductie', 'education' => 'Zelf opgegeven opleiding', 'specialties' => ['Schrijven'], 'is_public' => false, 'verification_status' => 'verified'];
        $this->asStaff($staff)->put('/werk/profiel', $data)->assertRedirect();
        $profile = $staff->fresh()->professionalProfile;
        $this->assertSame('unreviewed', $profile->verification_status);
        $this->get('/makers/'.$profile->public_id)->assertNotFound();
        $this->put('/werk/profiel', [...$data, 'is_public' => true])->assertRedirect();
        $this->get('/makers/'.$profile->public_id)->assertOk()->assertInertia(fn (Assert $page) => $page->component('profile')->where('profile.display_name', 'Maker A')->missing('profile.user_id')->missing('profile.email'));
        $staff->removeRole('staff');
        $this->get('/makers/'.$profile->public_id)->assertNotFound();
    }
}
