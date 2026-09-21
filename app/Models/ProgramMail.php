<?php

namespace App\Models;

use App\Jobs\SendProgramMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramMail extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['sent_at' => 'datetime', 'cancelled_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public static function once(string $key, int $user, string $kind, int $subject): self
    {
        $mail = static::firstOrCreate(['event_key' => $key], ['user_id' => $user, 'kind' => $kind, 'subject_id' => $subject]);
        if ($mail->wasRecentlyCreated) SendProgramMail::dispatch($mail->id)->afterCommit();
        return $mail;
    }
}
