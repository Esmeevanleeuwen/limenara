<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\ProgramVersion;
use Tests\TestCase;

class ProgramTest extends TestCase
{
    private function draft(int $authorId, string $status = 'draft'): Program
    {
        $p = new Program($this->programData()); $p->author_id = $authorId; $p->status = $status; $p->save(); return $p->fresh();
    }
    public function test_staff_can_create_drafts_but_not_assign_author_or_publish(): void
    {
        $staff = $this->member('staff', true);
        $this->asStaff($staff)->post('/werk/programmas', [...$this->programData(), 'author_id' => 999, 'status' => 'published'])->assertRedirect();
        $p = Program::firstOrFail();
        $this->assertSame($staff->id, $p->author_id); $this->assertSame('draft', $p->status);
        $this->get('/programmas/'.$p->id)->assertNotFound();
        $this->post('/beheer/programmas/'.$p->id.'/publiceren', ['revision' => $p->revision])->assertForbidden();
    }
    public function test_staff_cannot_read_or_modify_another_makers_draft(): void
    {
        $p = $this->draft($this->member('staff', true)->id);
        $this->asStaff($this->member('staff', true))->get('/werk/programmas/'.$p->id)->assertForbidden();
        $this->put('/werk/programmas/'.$p->id, [...$this->programData(), 'revision' => 1])->assertForbidden();
    }
    public function test_invalid_lesson_types_and_stale_edits_are_rejected(): void
    {
        $staff = $this->member('staff', true); $p = $this->draft($staff->id);
        $bad = $this->programData(); $bad['lessons'][0]['type'] = 'javascript';
        $this->asStaff($staff)->put('/werk/programmas/'.$p->id, [...$bad, 'revision' => 1])->assertSessionHasErrors('lessons.0.type');
        $this->put('/werk/programmas/'.$p->id, [...$this->programData('Nieuwe titel'), 'revision' => 1])->assertRedirect();
        $this->put('/werk/programmas/'.$p->id, [...$this->programData('Verouderde titel'), 'revision' => 1])->assertStatus(409);
        $this->assertSame('Nieuwe titel', $p->fresh()->title);
    }
    public function test_publication_creates_immutable_snapshot_and_existing_enrollments_keep_it(): void
    {
        $staff = $this->member('staff', true); $admin = $this->member('admin', true); $student = $this->member();
        $p = $this->draft($staff->id);
        $this->asStaff($staff)->post('/werk/programmas/'.$p->id.'/indienen', ['revision' => $p->revision])->assertRedirect();
        $this->asStaff($admin)->post('/beheer/programmas/'.$p->id.'/publiceren', ['revision' => $p->fresh()->revision])->assertRedirect();
        $version = ProgramVersion::firstOrFail();
        $this->actingAs($student)->post('/programmas/'.$p->id.'/deelnemen')->assertRedirect();
        $enrollment = Enrollment::firstOrFail();
        $this->post('/programmas/'.$p->id.'/deelnemen')->assertRedirect(); $this->assertDatabaseCount('enrollments', 1);
        $this->asStaff($staff)->put('/werk/programmas/'.$p->id, [...$this->programData('Versie twee'), 'revision' => $p->fresh()->revision])->assertRedirect();
        $this->assertSame('Een testprogramma', $version->fresh()->title);
        $this->assertSame($version->id, $p->fresh()->latestVersion->id);
        $this->post('/werk/programmas/'.$p->id.'/indienen', ['revision' => $p->fresh()->revision])->assertRedirect();
        $this->asStaff($admin)->post('/beheer/programmas/'.$p->id.'/publiceren', ['revision' => $p->fresh()->revision])->assertRedirect();
        $this->assertDatabaseCount('program_versions', 2);
        $this->assertSame($version->id, $enrollment->fresh()->program_version_id);
        $this->assertSame('Versie twee', $p->fresh()->latestVersion->title);
    }
    public function test_reviewer_cannot_publish_own_work_or_a_stale_revision(): void
    {
        $admin = $this->member('admin', true); $own = $this->draft($admin->id, 'review');
        $this->asStaff($admin)->post('/beheer/programmas/'.$own->id.'/publiceren', ['revision' => 1])->assertForbidden();
        $other = $this->draft($this->member('staff', true)->id, 'review');
        $this->post('/beheer/programmas/'.$other->id.'/publiceren', ['revision' => 999])->assertStatus(409);
        $this->assertDatabaseCount('program_versions', 0);
    }
    public function test_enrollment_and_progress_are_owner_only(): void
    {
        $owner = $this->member(); $p = $this->draft($this->member('staff', true)->id, 'published');
        $v = $p->versions()->create([...$this->programData(), 'number' => 1, 'published_by' => $this->member('admin', true)->id]);
        $e = Enrollment::create(['user_id' => $owner->id, 'program_id' => $p->id, 'program_version_id' => $v->id, 'completed_lessons' => []]);
        $this->actingAs($this->member())->get('/mijn-programmas/'.$e->id)->assertNotFound();
        $this->put('/mijn-programmas/'.$e->id.'/voortgang', ['lesson' => 0, 'completed' => true])->assertNotFound();
        $this->actingAs($owner)->put('/mijn-programmas/'.$e->id.'/voortgang', ['lesson' => 99, 'completed' => true])->assertStatus(422);
        $this->put('/mijn-programmas/'.$e->id.'/voortgang', ['lesson' => 0, 'completed' => true])->assertRedirect();
        $this->assertSame([0], $e->fresh()->completed_lessons);
        $this->put('/mijn-programmas/'.$e->id.'/voortgang', ['lesson' => 0, 'completed' => true]);
        $this->assertSame([0], $e->fresh()->completed_lessons);
    }
}
