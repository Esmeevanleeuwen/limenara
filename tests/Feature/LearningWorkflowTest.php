<?php

namespace Tests\Feature;

use App\Jobs\SendProgramMail;
use App\Models\Enrollment;
use App\Models\LessonResponse;
use App\Models\Program;
use App\Models\ProgramMail;
use App\Models\User;
use App\Notifications\ProgramUpdate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LearningWorkflowTest extends TestCase
{
    protected function setUp(): void { parent::setUp(); Queue::fake(); }
    private function course(?User $author = null): Program
    {
        $author ??= $this->member('staff', true);
        $p = new Program(config('program-templates.basis-overzicht'));
        $p->author_id = $author->id; $p->status = 'published'; $p->save();
        $p->versions()->create([...$p->only(['title', 'summary', 'goals', 'estimated_minutes', 'lessons']), 'number' => 1, 'published_by' => $this->member('admin', true)->id]);
        return $p->fresh();
    }
    private function enroll(Program $p, User $user): Enrollment
    {
        $this->actingAs($user)->post('/programmas/'.$p->id.'/deelnemen')->assertRedirect();
        return Enrollment::where('user_id', $user->id)->where('program_id', $p->id)->firstOrFail();
    }
    private function save(Enrollment $e, string $answer, bool $share = false, int $revision = 0)
    {
        return $this->put('/mijn-programmas/'.$e->id.'/antwoorden/1', compact('answer', 'share', 'revision'));
    }
    public function test_template_is_loaded_without_publication_or_automatic_clinical_role(): void
    {
        $staff = $this->member('staff', true);
        $this->asStaff($staff)->get('/werk/programmas/nieuw?template=basis-overzicht')->assertOk()->assertInertia(fn (Assert $page) => $page->component('staff/program-editor')->where('template.title', 'Meer overzicht in wat je ervaart')->has('template.lessons', 9));
        $this->assertDatabaseCount('programs', 0);
        $this->post('/werk/programmas', config('program-templates.basis-overzicht'))->assertRedirect();
        $this->assertSame('draft', Program::first()->status);
        $this->assertSame(40, Program::first()->estimated_minutes);
        $this->assertFalse($staff->hasRole('psychologist'));
        $this->get('/werk/programmas/nieuw?template=unknown')->assertNotFound();
        $this->actingAs($this->member())->get('/werk/programmas/nieuw?template=basis-overzicht')->assertForbidden();
    }
    public function test_incomplete_quizzes_and_empty_checklists_are_rejected(): void
    {
        $data = config('program-templates.basis-overzicht');
        $data['lessons'][3]['correct_option'] = 5;
        $data['lessons'][5]['items'] = [];
        $this->asStaff($this->member('staff', true))->post('/werk/programmas', $data)->assertSessionHasErrors(['lessons.3.correct_option', 'lessons.5.items']);
        $this->assertDatabaseCount('programs', 0);
    }
    public function test_invalid_nested_values_do_not_crash_validation(): void
    {
        $data = config('program-templates.basis-overzicht'); $data['lessons'][3]['question'] = ['x']; $data['lessons'][3]['options'] = 'bad';
        $this->asStaff($this->member('staff', true))->post('/werk/programmas', $data)->assertSessionHasErrors();
    }
    public function test_copy_has_no_enrollments_or_responses_and_is_owner_only(): void
    {
        $staff = $this->member('staff', true); $p = $this->course($staff); $this->enroll($p, $this->member());
        $this->asStaff($staff)->post('/werk/programmas/'.$p->id.'/kopieren')->assertRedirect();
        $copy = Program::latest('id')->first();
        $this->assertSame('draft', $copy->status); $this->assertSame($staff->id, $copy->author_id);
        $this->assertSame(0, $copy->versions()->count());
        $this->assertSame(0, Enrollment::where('program_id', $copy->id)->count());
        $this->asStaff($this->member('admin', true))->post('/werk/programmas/'.$p->id.'/kopieren')->assertForbidden();
    }
    public function test_registration_and_verification_keep_program_context_without_enrolling(): void
    {
        $p = $this->course();
        $this->get('/programmas/'.$p->id.'/start')->assertRedirect('/register')->assertSessionHas('intended_program_id', $p->id);
        $user = User::factory()->unverified()->create(); $user->assignRole('member');
        $this->actingAs($user)->get('/dashboard')->assertRedirect('/email/verify');
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(20), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url)->assertRedirect();
        $this->get('/dashboard')->assertRedirect('/programmas/'.$p->id);
        $this->assertDatabaseCount('enrollments', 0);
    }
    public function test_verification_mail_is_dutch_and_keeps_signed_link(): void
    {
        $u = User::factory()->unverified()->create();
        $message = (new \Illuminate\Auth\Notifications\VerifyEmail)->toMail($u);
        $this->assertSame('Bevestig je e-mailadres — Limenora', $message->subject);
        $this->assertStringContainsString('signature=', $message->actionUrl);
        $this->actingAs($u)->get($message->actionUrl)->assertRedirect();
        $this->assertTrue($u->fresh()->hasVerifiedEmail());
    }
    public function test_enrollment_and_completion_mails_are_deduplicated(): void
    {
        $p = $this->course(); $u = $this->member(); $e = $this->enroll($p, $u);
        $this->post('/programmas/'.$p->id.'/deelnemen')->assertRedirect();
        $this->assertSame(1, ProgramMail::where('kind', 'enrolled')->count());
        foreach ($e->version->lessons as $i => $lesson) $this->put('/mijn-programmas/'.$e->id.'/voortgang', ['lesson' => $i, 'completed' => true])->assertRedirect();
        $this->assertNotNull($e->fresh()->completed_at);
        $this->put('/mijn-programmas/'.$e->id.'/voortgang', ['lesson' => 0, 'completed' => false]);
        $this->assertNull($e->fresh()->completed_at);
        $this->put('/mijn-programmas/'.$e->id.'/voortgang', ['lesson' => 0, 'completed' => true]);
        $this->assertSame(1, ProgramMail::where('kind', 'completed')->count());
    }
    public function test_paused_course_blocks_edits_but_not_reading_or_withdrawal(): void
    {
        $e = $this->enroll($this->course(), $this->member());
        $this->save($e, 'Verzonnen testnotitie', true)->assertRedirect();
        $this->put('/mijn-programmas/'.$e->id.'/pauze', ['is_paused' => true])->assertRedirect();
        $this->get('/mijn-programmas/'.$e->id)->assertOk();
        $this->save($e, 'Verandering', true, 1)->assertStatus(409);
        $this->put('/mijn-programmas/'.$e->id.'/voortgang', ['lesson' => 0, 'completed' => true])->assertStatus(409);
        $this->delete('/mijn-programmas/'.$e->id.'/antwoorden/1/delen')->assertRedirect();
        $this->assertNull(LessonResponse::first()->shared_at);
        $this->put('/mijn-programmas/'.$e->id.'/pauze', ['is_paused' => false])->assertRedirect();
        $this->save($e, 'Verandering', false, 2)->assertRedirect();
    }
    public function test_answers_are_encrypted_private_by_default_and_not_exposed_to_author_or_admin(): void
    {
        $staff = $this->member('staff', true); $u = $this->member(); $e = $this->enroll($this->course($staff), $u);
        $this->save($e, 'PRIVATE SENTINEL 1842')->assertRedirect();
        $r = LessonResponse::firstOrFail();
        $this->assertSame('PRIVATE SENTINEL 1842', $r->answer);
        $this->assertStringNotContainsString('PRIVATE SENTINEL', DB::table('lesson_responses')->value('answer'));
        $this->assertArrayNotHasKey('answer', $r->toArray());
        $this->get('/mijn-programmas/'.$e->id)->assertInertia(fn (Assert $page) => $page->has('responses', 1)->where('responses.0.shared', false));
        $this->asStaff($staff)->get('/werk/inzendingen')->assertInertia(fn (Assert $page) => $page->has('responses.data', 0));
        $this->get('/werk/inzendingen/'.$r->id)->assertNotFound();
        $this->get('/mijn-programmas/'.$e->id)->assertNotFound();
        $this->asStaff($this->member('admin', true))->get('/werk/inzendingen/'.$r->id)->assertNotFound();
    }
    public function test_sharing_feedback_and_revocation_are_bound_to_the_named_maker(): void
    {
        $staff = $this->member('staff', true); $u = $this->member(); $e = $this->enroll($this->course($staff), $u);
        $this->save($e, 'Gedeeld testantwoord', true)->assertRedirect(); $r = LessonResponse::firstOrFail();
        $this->asStaff($this->member('staff', true))->get('/werk/inzendingen/'.$r->id)->assertNotFound();
        $this->asStaff($staff)->get('/werk/inzendingen/'.$r->id)->assertOk()->assertDontSee($u->email);
        $this->put('/werk/inzendingen/'.$r->id, ['feedback' => 'Testreactie', 'revision' => 1])->assertRedirect();
        $this->assertStringNotContainsString('Testreactie', DB::table('lesson_responses')->value('feedback'));
        $this->actingAs($u)->get('/mijn-programmas/'.$e->id)->assertInertia(fn (Assert $page) => $page->where('responses.0.feedback', 'Testreactie'));
        $this->delete('/mijn-programmas/'.$e->id.'/antwoorden/1/delen')->assertRedirect();
        $this->asStaff($staff)->put('/werk/inzendingen/'.$r->id, ['feedback' => 'Late reactie', 'revision' => 2])->assertNotFound();
        $this->get('/werk/inzendingen/'.$r->id)->assertNotFound();
    }
    public function test_revoked_staff_cannot_read_shared_answers_or_get_queued_mail(): void
    {
        $staff = $this->member('staff', true); $e = $this->enroll($this->course($staff), $this->member());
        $this->save($e, 'Test', true)->assertRedirect(); $r = LessonResponse::firstOrFail();
        $staff->removeRole('staff'); Notification::fake();
        $mail = ProgramMail::where('kind', 'shared')->firstOrFail(); (new SendProgramMail($mail->id))->handle();
        $this->assertNotNull($mail->fresh()->cancelled_at); Notification::assertNothingSent();
        $this->asStaff($staff)->get('/werk/inzendingen/'.$r->id)->assertForbidden();
    }
    public function test_answer_validation_and_stale_writes_are_checked(): void
    {
        $u = $this->member(); $e = $this->enroll($this->course(), $u);
        $this->save($e, 'Eerste')->assertRedirect();
        $this->save($e, 'Oud', false, 0)->assertStatus(409);
        $this->save($e, str_repeat('x', 4001), false, 1)->assertSessionHasErrors('answer');
        $this->put('/mijn-programmas/'.$e->id.'/antwoorden/3', ['answer' => 'Quiz', 'share' => false, 'revision' => 0])->assertStatus(422);
        $this->actingAs($this->member())->put('/mijn-programmas/'.$e->id.'/antwoorden/1', ['answer' => 'Niet van mij', 'share' => false, 'revision' => 1])->assertNotFound();
        $this->delete('/mijn-programmas/'.$e->id.'/antwoorden/1')->assertNotFound();
    }
    public function test_student_can_remove_test_response_and_its_feedback(): void
    {
        $u = $this->member(); $e = $this->enroll($this->course(), $u);
        $this->save($e, 'Test', true)->assertRedirect(); $r = LessonResponse::firstOrFail(); $r->update(['feedback' => 'Testreactie']);
        $this->delete('/mijn-programmas/'.$e->id.'/antwoorden/1')->assertRedirect(); $this->assertDatabaseCount('lesson_responses', 0);
        $this->assertDatabaseCount('enrollments', 1);
    }
    public function test_publication_and_return_notes_trigger_generic_messages(): void
    {
        $staff = $this->member('staff', true); $admin = $this->member('admin', true);
        $this->asStaff($staff)->post('/werk/programmas', config('program-templates.basis-overzicht'))->assertRedirect(); $p = Program::firstOrFail();
        $this->post('/werk/programmas/'.$p->id.'/indienen', ['revision' => 1])->assertRedirect();
        $this->asStaff($admin)->post('/beheer/programmas/'.$p->id.'/terugzetten', ['revision' => 2])->assertSessionHasErrors('review_note');
        $this->post('/beheer/programmas/'.$p->id.'/terugzetten', ['revision' => 2, 'review_note' => 'Controleer de bronnen.'])->assertRedirect();
        $this->assertSame('Controleer de bronnen.', $p->fresh()->review_note);
        $this->assertSame(1, ProgramMail::where('kind', 'returned')->count());
        $this->asStaff($staff)->post('/werk/programmas/'.$p->id.'/indienen', ['revision' => 3])->assertRedirect();
        $this->asStaff($admin)->post('/beheer/programmas/'.$p->id.'/publiceren', ['revision' => 4])->assertRedirect();
        $this->assertSame(1, ProgramMail::where('kind', 'published')->count());
    }
    public function test_mail_worker_sends_once_and_cancels_revoked_sharing(): void
    {
        Notification::fake(); $u = $this->member(); $e = $this->enroll($this->course(), $u);
        $mail = ProgramMail::where('kind', 'enrolled')->firstOrFail();
        (new SendProgramMail($mail->id))->handle(); (new SendProgramMail($mail->id))->handle();
        Notification::assertSentToTimes($u, ProgramUpdate::class, 1);
        $this->save($e, 'SENSITIVE SENTINEL', true)->assertRedirect();
        $this->delete('/mijn-programmas/'.$e->id.'/antwoorden/1/delen')->assertRedirect();
        $shared = ProgramMail::where('kind', 'shared')->firstOrFail(); (new SendProgramMail($shared->id))->handle();
        $this->assertNotNull($shared->fresh()->cancelled_at);
        $message = (new ProgramUpdate('shared', '/werk/inzendingen/1'))->toMail($u);
        $this->assertStringNotContainsString('SENSITIVE', json_encode($message->toArray()));
        $this->assertStringNotContainsString($e->version->title, json_encode($message->toArray()));
    }
    public function test_mail_retry_command_only_queues_unfinished_records(): void
    {
        $e = $this->enroll($this->course(), $this->member());
        $mail = ProgramMail::where('kind', 'enrolled')->firstOrFail();
        Queue::fake(); $this->artisan('limenora:retry-program-mail')->assertSuccessful();
        Queue::assertPushed(SendProgramMail::class, 1);
        $mail->update(['sent_at' => now()]); Queue::fake();
        $this->artisan('limenora:retry-program-mail')->assertSuccessful(); Queue::assertNothingPushed();
    }
}
