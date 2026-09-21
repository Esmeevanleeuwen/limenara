<?php

namespace App\Jobs;

use App\Models\Enrollment;
use App\Models\LessonResponse;
use App\Models\Program;
use App\Models\ProgramMail;
use App\Notifications\ProgramUpdate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SendProgramMail implements ShouldQueue
{
    use Queueable;
    public int $tries = 3;
    public int $timeout = 45;
    public function __construct(public int $mailId) {}
    public function backoff(): array { return [15, 60, 180]; }
    public function handle(): void
    {
        DB::transaction(function () {
            $mail = ProgramMail::whereKey($this->mailId)->lockForUpdate()->first();
            if (! $mail || $mail->sent_at || $mail->cancelled_at) return;
            $user = $mail->user;
            $path = null;
            if ($user?->hasVerifiedEmail()) {
                if (in_array($mail->kind, ['enrolled', 'completed'])) {
                    $e = Enrollment::find($mail->subject_id);
                    if ($e && $e->user_id === $user->id) $path = '/mijn-programmas/'.$e->id;
                } elseif (in_array($mail->kind, ['review', 'published', 'returned'])) {
                    $p = Program::find($mail->subject_id);
                    if ($p && $mail->kind === 'review' && $user->can('programs.review') && $p->author_id !== $user->id) $path = '/beheer/beoordelingen';
                    if ($p && $mail->kind !== 'review' && $user->can('programs.create') && $p->author_id === $user->id) $path = '/werk/programmas/'.$p->id;
                } else {
                    $r = LessonResponse::with('enrollment.program')->find($mail->subject_id);
                    if ($r && $mail->kind === 'shared' && $r->visibleToMaker($user)) $path = '/werk/inzendingen/'.$r->id;
                    if ($r && $mail->kind === 'feedback' && $r->enrollment->user_id === $user->id) $path = '/mijn-programmas/'.$r->enrollment_id;
                }
            }
            if (! $path) { $mail->update(['cancelled_at' => now()]); return; }
            // Generic notification only: no answers, titles, participant names or scores in SMTP.
            $user->notify(new ProgramUpdate($mail->kind, $path));
            $mail->update(['sent_at' => now()]);
        });
    }
}
