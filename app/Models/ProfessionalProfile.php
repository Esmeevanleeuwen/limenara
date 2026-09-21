<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalProfile extends Model
{
    protected $fillable = ['display_name', 'headline', 'biography', 'specialties', 'education', 'is_public'];

    protected function casts(): array
    {
        return ['specialties' => 'array', 'is_public' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
