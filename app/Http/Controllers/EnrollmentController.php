<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class EnrollmentController
{
    public function store(Request $request, Program $program)
    {
        $enrollment = DB::transaction(function () use ($request, $program) {
            // Lock the program to serialize concurrent enrollments/publication.
            $locked = Program::whereKey($program->id)->lockForUpdate()->firstOrFail();
            $version = $locked->latestVersion;
            abort_unless($version, 404);
            return Enrollment::firstOrCreate(['user_id' => $request->user()->id, 'program_id' => $program->id], ['program_version_id' => $version->id, 'completed_lessons' => []]);
        });
        return redirect('/mijn-programmas/'.$enrollment->id);
    }
    public function show(Request $request, Enrollment $enrollment)
    {
        abort_unless($enrollment->user_id === $request->user()->id, 404);
        return Inertia::render('programs/learn', ['enrollment' => $enrollment->only(['id', 'completed_lessons']), 'version' => $enrollment->version->only(['title', 'number', 'lessons'])]);
    }
    public function progress(Request $request, Enrollment $enrollment)
    {
        abort_unless($enrollment->user_id === $request->user()->id, 404);
        $data = $request->validate(['lesson' => ['required', 'integer', 'min:0'], 'completed' => ['required', 'boolean']]);
        DB::transaction(function () use ($enrollment, $data) {
            $locked = Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            abort_unless($data['lesson'] < count($locked->version->lessons), 422);
            $done = $locked->completed_lessons ?? [];
            $done = array_values(array_filter($done, fn ($n) => $n !== $data['lesson']));
            if ($data['completed']) $done[] = $data['lesson'];
            sort($done);
            $locked->update(['completed_lessons' => $done]);
        });
        return back();
    }
}
