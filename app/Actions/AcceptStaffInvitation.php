<?php

namespace App\Actions;

use App\Models\AuditEvent;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcceptStaffInvitation
{
    public function handle(User $user, string $hash): void
    {
        DB::transaction(function () use ($user, $hash) {
            $invitation = StaffInvitation::where('token_hash', $hash)->lockForUpdate()->first();
            if (! $invitation || ! $invitation->isUsable()) {
                throw ValidationException::withMessages(['invitation' => 'Deze uitnodiging is verlopen, ingetrokken of al gebruikt.']);
            }
            if (! $user->hasVerifiedEmail() || ! hash_equals($invitation->email, Str::lower($user->email))) {
                throw ValidationException::withMessages(['invitation' => 'Log in met het bevestigde e-mailadres waarvoor je bent uitgenodigd.']);
            }
            $user->assignRole('staff');
            if (! $user->professionalProfile()->exists()) {
                $profile = $user->professionalProfile()->make();
                $profile->public_id = (string) Str::uuid();
                $profile->save();
            }
            $invitation->update(['accepted_at' => now(), 'accepted_by' => $user->id]);
            AuditEvent::record($user->id, 'staff.invitation.accepted', $invitation->id);
        });
    }
}
