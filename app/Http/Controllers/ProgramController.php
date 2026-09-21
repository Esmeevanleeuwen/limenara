<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\ProgramMail;
use App\Models\User;
use App\Support\ProgramContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ProgramController
{
    private function draftData(Program $program): array
    {
        return $program->only(['id', 'title', 'summary', 'goals', 'estimated_minutes', 'lessons', 'status', 'revision', 'review_note']);
    }
    public function index()
    {
        $programs = Program::whereHas('latestVersion')->with('latestVersion')->latest()->paginate(12)
            ->through(fn (Program $p) => ['id' => $p->id, 'title' => $p->latestVersion->title, 'summary' => $p->latestVersion->summary, 'estimated_minutes' => $p->latestVersion->estimated_minutes, 'version' => $p->latestVersion->number]);
        return Inertia::render('programs/index', ['programs' => $programs]);
    }
    public function show(Program $program, Request $request)
    {
        $version = $program->latestVersion;
        abort_unless($version, 404);
        $enrollment = $request->user() ? Enrollment::where('user_id', $request->user()->id)->where('program_id', $program->id)->first(['id']) : null;
        return Inertia::render('programs/show', [
            'program' => ['id' => $program->id, 'title' => $version->title, 'summary' => $version->summary, 'goals' => $version->goals, 'estimated_minutes' => $version->estimated_minutes, 'version' => $version->number, 'lessonTitles' => array_column($version->lessons, 'title')],
            'enrollmentId' => $enrollment?->id,
        ]);
    }
    public function begin(Request $request, Program $program)
    {
        abort_unless($program->latestVersion, 404);
        $request->session()->put('intended_program_id', $program->id);
        if (! $request->user()) return redirect('/register');
        if (! $request->user()->hasVerifiedEmail()) return redirect('/email/verify');
        return redirect('/programmas/'.$program->id);
    }
    public function manage(Request $request)
    {
        Gate::authorize('programs.create');
        return Inertia::render('staff/programs', ['programs' => Program::where('author_id', $request->user()->id)->latest()->get(['id', 'title', 'status', 'updated_at'])]);
    }
    public function create(Request $request)
    {
        Gate::authorize('create', Program::class);
        $template = null;
        if ($request->filled('template')) {
            $key = $request->query('template');
            abort_unless(is_string($key) && array_key_exists($key, config('program-templates')), 404);
            $template = config('program-templates')[$key];
        }
        return Inertia::render('staff/program-editor', ['program' => null, 'template' => $template]);
    }
    public function store(Request $request)
    {
        Gate::authorize('create', Program::class);
        $program = new Program(ProgramContent::validate($request->all()));
        $program->author_id = $request->user()->id; $program->status = 'draft'; $program->save();
        return redirect('/werk/programmas/'.$program->id)->with('success', 'Concept aangemaakt. Nog niet openbaar.');
    }
    public function duplicate(Request $request, Program $program)
    {
        Gate::authorize('update', $program);
        $copy = new Program($program->only(['title', 'summary', 'goals', 'estimated_minutes', 'lessons']));
        $copy->title = mb_substr($program->title, 0, 150).' (kopie)';
        $copy->author_id = $request->user()->id; $copy->status = 'draft'; $copy->save();
        return redirect('/werk/programmas/'.$copy->id)->with('success', 'Eigen kopie gemaakt. Inschrijvingen en antwoorden zijn niet gekopieerd.');
    }
    public function edit(Program $program)
    {
        Gate::authorize('update', $program);
        return Inertia::render('staff/program-editor', ['program' => $this->draftData($program), 'template' => null]);
    }
    public function update(Request $request, Program $program)
    {
        Gate::authorize('update', $program);
        $data = ProgramContent::validate($request->all());
        $revision = $request->validate(['revision' => ['required', 'integer', 'min:1']])['revision'];
        DB::transaction(function () use ($program, $data, $revision) {
            $locked = Program::whereKey($program->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->revision === $revision, 409, 'Dit concept is ondertussen gewijzigd. Vernieuw de pagina voordat je verdergaat.');
            $locked->fill($data); $locked->status = 'draft'; $locked->revision++; $locked->save();
        });
        return redirect('/werk/programmas/'.$program->id)->with('success', 'Concept opgeslagen. Een bestaande publicatie blijft ongewijzigd.');
    }
    public function submit(Request $request, Program $program)
    {
        Gate::authorize('update', $program);
        $revision = $request->validate(['revision' => ['required', 'integer']])['revision'];
        DB::transaction(function () use ($request, $program, $revision) {
            $locked = Program::whereKey($program->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'draft' && $locked->revision === $revision, 409);
            ProgramContent::validate($locked->toArray());
            $locked->status = 'review'; $locked->review_note = null; $locked->revision++; $locked->save();
            AuditEvent::record($request->user()->id, 'program.submitted', $locked->id);
            foreach (User::permission('programs.review')->where('id', '!=', $locked->author_id)->whereNotNull('email_verified_at')->cursor() as $reviewer) {
                ProgramMail::once('review:'.$locked->id.':'.$locked->revision.':'.$reviewer->id, $reviewer->id, 'review', $locked->id);
            }
        });
        return redirect('/werk/programmas/'.$program->id)->with('success', 'Concept ingediend. Beoordelaars ontvangen een melding.');
    }
    public function reviews(Request $request)
    {
        Gate::authorize('programs.review');
        return Inertia::render('admin/reviews', ['programs' => Program::where('status', 'review')->where('author_id', '!=', $request->user()->id)->latest()->get()->map(fn ($p) => $this->draftData($p))]);
    }
    public function publish(Request $request, Program $program)
    {
        Gate::authorize('review', $program);
        abort_if($program->author_id === $request->user()->id, 403, 'Je kunt je eigen programma niet goedkeuren.');
        $revision = $request->validate(['revision' => ['required', 'integer']])['revision'];
        DB::transaction(function () use ($request, $program, $revision) {
            $locked = Program::whereKey($program->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'review' && $locked->revision === $revision, 409, 'Het concept is gewijzigd. Beoordeel de nieuwste versie.');
            ProgramContent::validate($locked->toArray());
            $locked->versions()->create([
                ...$locked->only(['title', 'summary', 'goals', 'estimated_minutes', 'lessons']),
                'number' => ((int) $locked->versions()->max('number')) + 1,
                'published_by' => $request->user()->id,
            ]);
            $locked->status = 'published'; $locked->revision++; $locked->save();
            AuditEvent::record($request->user()->id, 'program.published', $locked->id);
            ProgramMail::once('published:'.$locked->id.':'.$locked->revision, $locked->author_id, 'published', $locked->id);
        });
        return redirect('/beheer/beoordelingen')->with('success', 'Nieuwe educatieve programmaversie gepubliceerd. Dit is geen behandelgoedkeuring.');
    }
    public function returnDraft(Request $request, Program $program)
    {
        Gate::authorize('review', $program);
        abort_if($program->author_id === $request->user()->id, 403);
        $data = $request->validate(['revision' => ['required', 'integer'], 'review_note' => ['required', 'string', 'max:2000']]);
        DB::transaction(function () use ($request, $program, $data) {
            $locked = Program::whereKey($program->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'review' && $locked->revision === $data['revision'], 409);
            $locked->status = 'draft'; $locked->review_note = $data['review_note']; $locked->revision++; $locked->save();
            AuditEvent::record($request->user()->id, 'program.returned', $locked->id);
            ProgramMail::once('returned:'.$locked->id.':'.$locked->revision, $locked->author_id, 'returned', $locked->id);
        });
        return redirect('/beheer/beoordelingen')->with('success', 'Programma met toelichting teruggestuurd naar de maker.');
    }
}
