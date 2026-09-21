<?php

namespace App\Http\Controllers;

use App\Actions\AcceptStaffInvitation;
use App\Models\AuditEvent;
use App\Models\StaffInvitation;
use App\Models\User;
use App\Notifications\StaffInvited;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InvitationController
{
    public function index()
    {
        Gate::authorize('staff.invite');
        return Inertia::render('admin/staff', [
            'invitations' => StaffInvitation::latest()->limit(50)->get(['id', 'email', 'role', 'expires_at', 'accepted_at', 'revoked_at']),
            'staff' => User::role('staff')->get(['id', 'name', 'email']),
        ]);
    }
    public function store(Request $request)
    {
        Gate::authorize('staff.invite');
        $data = $request->validate(['email' => ['required', 'email', 'max:254'], 'role' => ['required', Rule::in(['staff'])]]);
        $token = Str::random(64);
        $invitation = DB::transaction(function () use ($request, $data, $token) {
            $email = Str::lower(trim($data['email']));
            StaffInvitation::where('email', $email)->whereNull('accepted_at')->whereNull('revoked_at')->update(['revoked_at' => now()]);
            $invitation = StaffInvitation::create(['email' => $email, 'role' => 'staff', 'token_hash' => hash('sha256', $token), 'invited_by' => $request->user()->id, 'expires_at' => now()->addDays(7)]);
            AuditEvent::record($request->user()->id, 'staff.invitation.created', $invitation->id);
            return $invitation;
        });
        try {
            Notification::route('mail', $invitation->email)->notify(new StaffInvited(url('/uitnodiging/'.$token)));
        } catch (\Throwable $exception) {
            $invitation->update(['revoked_at' => now()]);
            // Do not log token-bearing notification contents or provider error bodies.
            return back()->with('error', 'Versturen is mislukt. Controleer de e-mailinstellingen en verstuur een nieuwe uitnodiging.');
        }
        return back()->with('success', 'Uitnodiging verstuurd. Lokaal vind je de e-mail in Mailpit.');
    }
    public function revoke(Request $request, StaffInvitation $invitation)
    {
        Gate::authorize('staff.invite');
        DB::transaction(function () use ($request, $invitation) {
            $locked = StaffInvitation::whereKey($invitation->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->accepted_at, 409, 'Gebruik bij een geaccepteerde uitnodiging Medewerker intrekken.');
            $locked->update(['revoked_at' => now()]);
            AuditEvent::record($request->user()->id, 'staff.invitation.revoked', $locked->id);
        });
        return back()->with('success', 'Uitnodiging ingetrokken.');
    }
    public function removeStaff(Request $request, User $user)
    {
        Gate::authorize('staff.revoke');
        abort_if($user->hasRole('admin') || $user->id === $request->user()->id, 403);
        DB::transaction(function () use ($request, $user) {
            $user->removeRole('staff');
            $user->professionalProfile()->update(['is_public' => false]);
            DB::table('sessions')->where('user_id', $user->id)->delete();
            AuditEvent::record($request->user()->id, 'staff.access.revoked', $user->id);
        });
        return back()->with('success', 'Medewerkerrechten ingetrokken. Het gewone account blijft bestaan.');
    }
    public function open(Request $request, string $token)
    {
        $hash = hash('sha256', $token);
        $invitation = StaffInvitation::where('token_hash', $hash)->first();
        abort_unless($invitation?->isUsable(), 410, 'Deze uitnodiging is niet meer beschikbaar.');
        $request->session()->put('pending_invitation_hash', $hash);
        // Remove the raw token from the address bar before login/registration.
        return redirect('/uitnodiging');
    }
    public function show(Request $request)
    {
        $invitation = StaffInvitation::where('token_hash', (string) $request->session()->get('pending_invitation_hash', ''))->first();
        abort_unless($invitation?->isUsable(), 410, 'Open de geldige link uit je uitnodigingsmail opnieuw.');
        return Inertia::render('invitation', ['emailHint' => Str::mask($invitation->email, '*', 1, max(1, strpos($invitation->email, '@') - 1)), 'matches' => $request->user() && hash_equals($invitation->email, Str::lower($request->user()->email))]);
    }
    public function accept(Request $request, AcceptStaffInvitation $action)
    {
        $action->handle($request->user(), (string) $request->session()->get('pending_invitation_hash', ''));
        $request->session()->forget('pending_invitation_hash');
        $request->session()->regenerate();
        return redirect('/settings/security')->with('success', 'Welkom! Stel tweestapsverificatie in voordat je jouw werkomgeving opent.');
    }
}
