<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $guarded = ['id'];
    protected function casts(): array { return ['completed_lessons' => 'array']; }
    public function version(): BelongsTo { return $this->belongsTo(ProgramVersion::class, 'program_version_id'); }
}
