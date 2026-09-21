<?php

namespace App\Policies;

use App\Models\Program;
use App\Models\User;

class ProgramPolicy
{
    public function create(User $user): bool { return $user->can('programs.create'); }
    public function update(User $user, Program $program): bool
    {
        return $user->can('programs.create') && $program->author_id === $user->id;
    }
    public function review(User $user, Program $program): bool { return $user->can('programs.review'); }
}
