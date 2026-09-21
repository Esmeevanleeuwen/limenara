<?php

use App\Models\AuditEvent;
use App\Models\StaffInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('limenora:admin {email : Bestaand en bevestigd e-mailadres} {--revoke : Trek de beheerrol in}', function () {
    $user = User::where('email', Str::lower(trim($this->argument('email'))))->first();
    if (! $user || ! $user->hasVerifiedEmail()) {
        $this->error('Registreer eerst via de website en bevestig het e-mailadres.'); return 1;
    }
    $revoke = $this->option('revoke');
    if ($revoke && User::role('admin')->count() <= 1 && $user->hasRole('admin')) {
        $this->error('De laatste beheerder kan niet worden verwijderd.'); return 1;
    }
    if (! $this->confirm(($revoke ? 'Beheerrechten intrekken voor ' : 'Beheerrechten geven aan ').$user->email.'?', false)) return 1;
    $revoke ? $user->removeRole('admin') : $user->assignRole('admin');
    AuditEvent::record($user->id, $revoke ? 'admin.revoked.via.console' : 'admin.granted.via.console', $user->id);
    $this->info('Opgeslagen. Voor de werkomgeving blijven e-mailbevestiging en tweestapsverificatie verplicht.');
    return 0;
})->purpose('Beheerrol toekennen aan een bestaand account; geen standaardwachtwoorden.');

Artisan::command('limenora:prune-invitations', function () {
    $count = StaffInvitation::where('expires_at', '<', now()->subDays(30))->delete();
    $this->info($count.' oude uitnodigingen verwijderd.');
});
Schedule::command('limenora:prune-invitations')->daily();
