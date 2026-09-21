<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonResponse extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['answer', 'feedback'];
    protected function casts(): array
    {
        return ['answer' => 'encrypted', 'feedback' => 'encrypted', 'shared_at' => 'datetime', 'feedback_at' => 'datetime', 'revision' => 'integer'];
    }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
    public function visibleToMaker(User $user): bool
    {
        return $this->shared_at !== null && $user->hasVerifiedEmail() && $user->can('programs.respond')
            && $this->enrollment->program->author_id === $user->id
            && $this->enrollment->user_id !== $user->id;
    }
    public function forReader(): array
    {
        return ['id' => $this->id, 'lesson_index' => $this->lesson_index, 'answer' => $this->answer,
            'shared' => $this->shared_at !== null, 'feedback' => $this->feedback,
            'feedback_at' => $this->feedback_at?->toIso8601String(), 'revision' => $this->revision];
    }
}
