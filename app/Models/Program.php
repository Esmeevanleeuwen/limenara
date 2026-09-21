<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Program extends Model
{
    protected $fillable = ['title', 'summary', 'goals', 'estimated_minutes', 'lessons'];
    protected function casts(): array { return ['lessons' => 'array']; }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
    public function versions(): HasMany { return $this->hasMany(ProgramVersion::class); }
    public function latestVersion(): HasOne { return $this->hasOne(ProgramVersion::class)->ofMany('number', 'max'); }
}
