<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['completed_lessons' => 'array', 'is_paused' => 'boolean', 'completed_at' => 'datetime']; }
    public function version(): BelongsTo { return $this->belongsTo(ProgramVersion::class, 'program_version_id'); }
    public function program(): BelongsTo { return $this->belongsTo(Program::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function responses(): HasMany { return $this->hasMany(LessonResponse::class); }
}
