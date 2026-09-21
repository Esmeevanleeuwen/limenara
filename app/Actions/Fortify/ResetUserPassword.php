<?php

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    public function reset($user, array $input): void
    {
        Validator::make($input, ['password' => ['required', 'string', 'max:128', Password::min(12), 'confirmed']])->validate();
        $user->forceFill(['password' => $input['password'], 'remember_token' => Str::random(60)])->save();
        // Invalidate existing sessions as well as remember-me tokens.
        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
    }
}
