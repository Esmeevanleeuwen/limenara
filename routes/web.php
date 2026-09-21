<?php

use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonResponseController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\SecurityController;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('home'))->name('home');
Route::get('/privacy', fn () => Inertia::render('privacy'))->name('privacy');
Route::get('/programmas', [ProgramController::class, 'index'])->name('programs.index');
Route::get('/programmas/{program}/start', [ProgramController::class, 'begin']);
Route::get('/programmas/{program}', [ProgramController::class, 'show'])->name('programs.show');
Route::get('/makers', [ProfileController::class, 'index'])->name('profiles.index');
Route::get('/makers/{publicId}', [ProfileController::class, 'show'])->whereUuid('publicId')->name('profiles.show');
Route::get('/uitnodiging/{token}', [InvitationController::class, 'open'])->where('token', '[A-Za-z0-9]{64}')->middleware('throttle:10,1');
Route::get('/uitnodiging', [InvitationController::class, 'show'])->name('invitation.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function (Request $r) {
        if ($r->session()->has('pending_invitation_hash')) return redirect('/uitnodiging');
        if ($programId = $r->session()->pull('intended_program_id')) {
            if (\App\Models\Program::whereKey($programId)->whereHas('latestVersion')->exists()) return redirect('/programmas/'.$programId);
        }
        $enrollments = Enrollment::where('user_id', $r->user()->id)->with('version')->latest()->get()->map(fn ($e) => [
            'id' => $e->id, 'title' => $e->version->title, 'version' => $e->version->number,
            'is_paused' => $e->is_paused, 'done' => count($e->completed_lessons ?? []), 'total' => count($e->version->lessons),
        ]);
        return Inertia::render('dashboard', ['enrollments' => $enrollments]);
    })->name('dashboard');
    Route::post('/uitnodiging', [InvitationController::class, 'accept'])->middleware('throttle:invitations');
    Route::post('/uitnodiging/overslaan', function (Request $r) { $r->session()->forget('pending_invitation_hash'); return redirect('/dashboard'); });
    Route::get('/settings/security', [SecurityController::class, 'show'])->name('security');
    Route::get('/settings/security/credentials', [SecurityController::class, 'show'])->middleware('password.confirm');
    Route::post('/settings/security/unlock', [SecurityController::class, 'unlock'])->middleware('throttle:mfa-unlock');
    Route::post('/programmas/{program}/deelnemen', [EnrollmentController::class, 'store'])->middleware('throttle:platform-write');
    Route::get('/mijn-programmas/{enrollment}', [EnrollmentController::class, 'show']);
    Route::put('/mijn-programmas/{enrollment}/pauze', [EnrollmentController::class, 'pause'])->middleware('throttle:platform-write');
    Route::put('/mijn-programmas/{enrollment}/antwoorden/{lesson}', [LessonResponseController::class, 'save'])->whereNumber('lesson')->middleware('throttle:platform-write');
    Route::delete('/mijn-programmas/{enrollment}/antwoorden/{lesson}/delen', [LessonResponseController::class, 'unshare'])->whereNumber('lesson')->middleware('throttle:platform-write');
    Route::delete('/mijn-programmas/{enrollment}/antwoorden/{lesson}', [LessonResponseController::class, 'delete'])->whereNumber('lesson')->middleware('throttle:platform-write');
    Route::put('/mijn-programmas/{enrollment}/voortgang', [EnrollmentController::class, 'progress'])->middleware('throttle:platform-write');

    Route::middleware(['can:programs.create', 'mfa'])->prefix('werk')->group(function () {
        Route::get('/profiel', [ProfileController::class, 'edit']);
        Route::put('/profiel', [ProfileController::class, 'update'])->middleware('throttle:platform-write');
        Route::get('/programmas', [ProgramController::class, 'manage']);
        Route::get('/programmas/nieuw', [ProgramController::class, 'create']);
        Route::post('/programmas', [ProgramController::class, 'store'])->middleware('throttle:platform-write');
        Route::get('/programmas/{program}', [ProgramController::class, 'edit']);
        Route::put('/programmas/{program}', [ProgramController::class, 'update'])->middleware('throttle:platform-write');
        Route::post('/programmas/{program}/kopieren', [ProgramController::class, 'duplicate'])->middleware('throttle:platform-write');
        Route::post('/programmas/{program}/indienen', [ProgramController::class, 'submit'])->middleware('throttle:platform-write');
    });
    Route::middleware(['can:programs.respond', 'mfa'])->prefix('werk')->group(function () {
        Route::get('/inzendingen', [LessonResponseController::class, 'index']);
        Route::get('/inzendingen/{response}', [LessonResponseController::class, 'show']);
        Route::put('/inzendingen/{response}', [LessonResponseController::class, 'feedback'])->middleware('throttle:platform-write');
    });
    Route::middleware(['can:staff.invite', 'mfa'])->prefix('beheer')->group(function () {
        Route::get('/medewerkers', [InvitationController::class, 'index']);
        Route::post('/uitnodigingen', [InvitationController::class, 'store'])->middleware('throttle:invitations');
        Route::delete('/uitnodigingen/{invitation}', [InvitationController::class, 'revoke']);
        Route::delete('/medewerkers/{user}', [InvitationController::class, 'removeStaff']);
    });
    Route::middleware(['can:programs.review', 'mfa'])->prefix('beheer')->group(function () {
        Route::get('/beoordelingen', [ProgramController::class, 'reviews']);
        Route::post('/programmas/{program}/publiceren', [ProgramController::class, 'publish'])->middleware('throttle:platform-write');
        Route::post('/programmas/{program}/terugzetten', [ProgramController::class, 'returnDraft'])->middleware('throttle:platform-write');
    });
});
