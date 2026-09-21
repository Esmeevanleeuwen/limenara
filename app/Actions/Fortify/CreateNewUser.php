<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        $input['email'] = Str::lower(trim($input['email'] ?? ''));
        Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:254', 'unique:users'],
            'password' => ['required', 'string', 'max:128', Password::min(12), 'confirmed'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create(['name' => $input['name'], 'email' => $input['email'], 'password' => $input['password']]);
            $user->assignRole('member'); // Ignore any role/permission input from registration.
            return $user;
        });
    }
}
