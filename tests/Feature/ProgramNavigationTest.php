<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\LessonResponse;
use App\Models\Program;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProgramNavigationTest extends TestCase
{
    public function test_mutations_return_to_their_own_screen_without_a_referrer(): void
    {
        Queue::fake();
        $maker = $this->member('staff', true);
        $admin = $this->member('admin', true);
        $this->asStaff($maker)->post('/werk/programmas', config('program-templates.basis-overzicht'))->assertRedirect();
        $p = Program::firstOrFail();
        $editor = '/werk/programmas/'.$p->id;
        $this->withSession(['_previous.url' => url('/programmas')])
            ->put($editor, [...config('program-templates.basis-overzicht'), 'revision' => 1])->assertRedirect($editor);
        $this->post($editor.'/indienen', ['revision' => 2])->assertRedirect($editor);
        $this->asStaff($admin)->post('/beheer/programmas/'.$p->id.'/publiceren', ['revision' => 3])->assertRedirect('/beheer/beoordelingen');
        $member = $this->member();
        $this->actingAs($member)->post('/programmas/'.$p->id.'/deelnemen')->assertRedirect();
        $e = Enrollment::firstOrFail(); $learner = '/mijn-programmas/'.$e->id;
        $this->withSession(['_previous.url' => url('/programmas/'.$p->id)])
            ->put($learner.'/antwoorden/1', ['answer' => 'Fictieve test', 'share' => true, 'revision' => 0])->assertRedirect($learner);
        $r = LessonResponse::firstOrFail();
        $this->asStaff($maker)->put('/werk/inzendingen/'.$r->id, ['feedback' => 'Fictieve reactie', 'revision' => 1])->assertRedirect('/werk/inzendingen/'.$r->id);
        $this->actingAs($member)->put($learner.'/voortgang', ['lesson' => 0, 'completed' => true])->assertRedirect($learner);
        $this->put($learner.'/pauze', ['is_paused' => true])->assertRedirect($learner);
        $this->delete($learner.'/antwoorden/1/delen')->assertRedirect($learner);
        $this->delete($learner.'/antwoorden/1')->assertRedirect($learner);
    }
    public function test_validation_returns_to_the_form_without_flashing_private_input(): void
    {
        Queue::fake();
        $maker = $this->member('staff', true);
        $this->asStaff($maker)->withSession(['_previous.url' => url('/programmas')])
            ->post('/werk/programmas', [...config('program-templates.basis-overzicht'), 'title' => ''])
            ->assertRedirect('/werk/programmas/nieuw')->assertSessionHasErrors('title');
        $p = new Program(config('program-templates.basis-overzicht'));
        $p->author_id = $maker->id; $p->status = 'published'; $p->save();
        $v = $p->versions()->create([...$p->only(['title', 'summary', 'goals', 'estimated_minutes', 'lessons']), 'number' => 1, 'published_by' => $this->member('admin', true)->id]);
        $member = $this->member();
        $e = Enrollment::create(['user_id' => $member->id, 'program_id' => $p->id, 'program_version_id' => $v->id, 'completed_lessons' => []]);
        $this->actingAs($member)->withSession(['_previous.url' => url('/programmas')])
            ->put('/mijn-programmas/'.$e->id.'/antwoorden/1', ['answer' => str_repeat('x', 4001), 'share' => false, 'revision' => 0])
            ->assertRedirect('/mijn-programmas/'.$e->id)->assertSessionHasErrors('answer')
            ->assertSessionMissing('_old_input.answer');
    }
}
