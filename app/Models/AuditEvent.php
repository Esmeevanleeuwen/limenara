<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditEvent extends Model
{
    protected $guarded = ['id'];

    // No emails, tokens, passwords, course answers or medical content in this log.
    public static function record(int $actorId, string $action, ?int $subjectId = null): void
    {
        static::create(['actor_id' => $actorId, 'action' => $action, 'subject_id' => $subjectId]);
    }
}
