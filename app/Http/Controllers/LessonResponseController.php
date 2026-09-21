<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use App\Models\Enrollment;
use App\Models\LessonResponse;
use App\Models\ProgramMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LessonResponseController
{
    public function save(Request $request, Enrollment $enrollment, int $lesson)
    {
        abort_unless($enrollment->user_id === $request->user()->id, 404);
        $data = $request->validate(['answer' => ['required', 'string', 'max:4000'], 'share' => ['required', 'boolean'], 'revision' => ['required', 'integer', 'min:0']]);
        DB::transaction(function () use ($request, $enrollment, $lesson, $data) {
            $e = Enrollment::whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
            abort_if($e->is_paused, 409, 'Hervat eerst het programma.');
            $item = $e->version->lessons[$lesson] ?? null;
            abort_unless($item && in_array($item['type'], ['reflection', 'action']), 422);
            $response = $e->responses()->where('lesson_index', $lesson)->lockForUpdate()->first();
            abort_unless(($response?->revision ?? 0) === $data['revision'], 409, 'Dit antwoord is veranderd. Vernieuw eerst de pagina.');
            $author = $e->program->author;
            if ($data['share']) abort_unless($author && $author->hasVerifiedEmail() && $author->can('programs.respond') && $author->id !== $request->user()->id, 422, 'De maker kan momenteel geen gedeelde antwoorden ontvangen. Je kunt privé opslaan.');
            $response ??= new LessonResponse(['enrollment_id' => $e->id, 'lesson_index' => $lesson]);
            $response->answer = $data['answer'];
            $response->shared_at = $data['share'] ? ($response->shared_at ?? now()) : null;
            $response->revision = $data['revision'] + 1;
            $response->save();
            AuditEvent::record($request->user()->id, $data['share'] ? 'response.shared' : 'response.private', $response->id);
            if ($data['share']) ProgramMail::once('shared:'.$response->id.':'.$response->revision, $author->id, 'shared', $response->id);
        });
        return redirect('/mijn-programmas/'.$enrollment->id)->with('success', $data['share'] ? 'Opgeslagen en alleen met de genoemde maker gedeeld.' : 'Privé opgeslagen. De maker kan dit antwoord niet lezen.');
    }

    public function unshare(Request $request, Enrollment $enrollment, int $lesson)
    {
        abort_unless($enrollment->user_id === $request->user()->id, 404);
        DB::transaction(function () use ($request, $enrollment, $lesson) {
            $r = $enrollment->responses()->where('lesson_index', $lesson)->lockForUpdate()->firstOrFail();
            $r->shared_at = null; $r->revision++; $r->save();
            AuditEvent::record($request->user()->id, 'response.unshared', $r->id);
        });
        return redirect('/mijn-programmas/'.$enrollment->id)->with('success', 'Toegang voor de maker ingetrokken. Al gelezen of gekopieerde informatie kan niet worden teruggehaald.');
    }

    public function delete(Request $request, Enrollment $enrollment, int $lesson)
    {
        abort_unless($enrollment->user_id === $request->user()->id, 404);
        DB::transaction(function () use ($request, $enrollment, $lesson) {
            $r = $enrollment->responses()->where('lesson_index', $lesson)->lockForUpdate()->firstOrFail();
            AuditEvent::record($request->user()->id, 'response.deleted', $r->id);
            $r->delete();
        });
        return redirect('/mijn-programmas/'.$enrollment->id)->with('success', 'Dit testantwoord en de bijbehorende reactie zijn verwijderd.');
    }

    public function index(Request $request)
    {
        $responses = LessonResponse::whereNotNull('shared_at')
            ->whereHas('enrollment', fn ($q) => $q->where('user_id', '!=', $request->user()->id)
                ->whereHas('program', fn ($p) => $p->where('author_id', $request->user()->id)))
            ->with(['enrollment.user', 'enrollment.version'])->latest()->paginate(15)
            ->through(fn ($r) => ['id' => $r->id, 'participant' => $r->enrollment->user->name,
                'title' => $r->enrollment->version->title, 'lesson' => $r->enrollment->version->lessons[$r->lesson_index]['title'],
                'has_feedback' => $r->feedback_at !== null]);
        return Inertia::render('staff/responses', ['responses' => $responses]);
    }

    public function show(Request $request, LessonResponse $response)
    {
        abort_unless($response->visibleToMaker($request->user()), 404);
        return Inertia::render('staff/response', ['response' => [...$response->forReader(),
            'participant' => $response->enrollment->user->name, 'title' => $response->enrollment->version->title,
            'lesson' => $response->enrollment->version->lessons[$response->lesson_index]]]);
    }

    public function feedback(Request $request, LessonResponse $response)
    {
        abort_unless($response->visibleToMaker($request->user()), 404);
        $data = $request->validate(['feedback' => ['required', 'string', 'max:4000'], 'revision' => ['required', 'integer', 'min:1']]);
        DB::transaction(function () use ($request, $response, $data) {
            $r = LessonResponse::whereKey($response->id)->lockForUpdate()->firstOrFail();
            abort_unless($r->visibleToMaker($request->user()), 404);
            abort_unless($r->revision === $data['revision'], 409, 'Het antwoord is gewijzigd. Lees de nieuwste versie voordat je reageert.');
            $r->feedback = $data['feedback']; $r->feedback_at = now(); $r->revision++; $r->save();
            AuditEvent::record($request->user()->id, 'response.feedback', $r->id);
            ProgramMail::once('feedback:'.$r->id.':'.$r->revision, $r->enrollment->user_id, 'feedback', $r->id);
        });
        return redirect('/werk/inzendingen/'.$response->id)->with('success', 'Reactie opgeslagen. De deelnemer krijgt een neutrale e-mailmelding.');
    }
}
