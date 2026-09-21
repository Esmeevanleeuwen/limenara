<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Program;
use App\Models\ProgramMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EnrollmentController
{
    public function store(Request $request, Program $program)
    {
        $enrollment = DB::transaction(function () use ($request, $program) {
            $locked = Program::whereKey($program->id)->lockForUpdate()->firstOrFail();
            $version = $locked->latestVersion;
            abort_unless($version, 404);
            $e = Enrollment::firstOrCreate(['user_id' => $request->user()->id, 'program_id' => $program->id], ['program_version_id' => $version->id, 'completed_lessons' => []]);
            ProgramMail::once('enrolled:'.$e->id, $request->user()->id, 'enrolled', $e->id);
            return $e;
        });
        $request->session()->forget('intended_program_id');
        return redirect('/mijn-programmas/'.$enrollment->id);
    }
    public function show(Request $request, Enrollment $enrollment)
    {
        abort_unless($enrollment->user_id === $request->user()->id, 404);
        $author = $enrollment->program->author;
        return Inertia::render('programs/learn', [
            'enrollment' => $enrollment->only(['id', 'completed_lessons', 'is_paused', 'completed_at']),
            'version' => $enrollment->version->only(['title', 'number', 'lessons']),
            'responses' => $enrollment->responses()->get()->map(fn ($r) => $r->forReader()),
            'maker' => ['name' => $author->professionalProfile?->display_name ?: 'De programmamaker',
                'can_receive' => $author->hasVerifiedEmail() && $author->can('programs.respond') && $author->id !== $request->user()->id],
        ]);
    }
    public function pause(Request $request, Enrollment $enrollment)
    {
        abort_unless($enrollment->user_id === $request->user()->id, 404);
        $data = $request->validate(['is_paused' => ['required', 'boolean']]);
        DB::transaction(function () use ($enrollment, $data) {
            Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail()->update($data);
        });
        return redirect('/mijn-programmas/'.$enrollment->id)->with('success', $data['is_paused'] ? 'Gepauzeerd. Je kunt blijven teruglezen en delen intrekken. Er worden geen voortgangsherinneringen gestuurd.' : 'Je kunt weer verdergaan op je eigen tempo.');
    }
    public function progress(Request $request, Enrollment $enrollment)
    {
        abort_unless($enrollment->user_id === $request->user()->id, 404);
        $data = $request->validate(['lesson' => ['required', 'integer', 'min:0'], 'completed' => ['required', 'boolean']]);
        DB::transaction(function () use ($enrollment, $data) {
            $locked = Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->is_paused, 409, 'Hervat eerst het programma.');
            abort_unless($data['lesson'] < count($locked->version->lessons), 422);
            $done = array_values(array_filter($locked->completed_lessons ?? [], fn ($n) => $n !== $data['lesson']));
            if ($data['completed']) $done[] = $data['lesson'];
            sort($done);
            $complete = count($done) === count($locked->version->lessons);
            $locked->update(['completed_lessons' => $done, 'completed_at' => $complete ? ($locked->completed_at ?? now()) : null]);
            if ($complete) ProgramMail::once('completed:'.$locked->id, $locked->user_id, 'completed', $locked->id);
        });
        return redirect('/mijn-programmas/'.$enrollment->id);
    }
}
