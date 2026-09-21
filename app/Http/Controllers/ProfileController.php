<?php

namespace App\Http\Controllers;

use App\Models\ProfessionalProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProfileController
{
    public function edit(Request $request)
    {
        Gate::authorize('profile.manage');
        $profile = $request->user()->professionalProfile;
        return Inertia::render('staff/profile', ['profile' => $profile]);
    }
    public function update(Request $request)
    {
        Gate::authorize('profile.manage');
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:120'], 'headline' => ['nullable', 'string', 'max:180'],
            'biography' => ['nullable', 'string', 'max:5000'], 'education' => ['nullable', 'string', 'max:3000'],
            'specialties' => ['present', 'array', 'max:12'], 'specialties.*' => ['required', 'string', 'distinct', 'max:80'],
            'is_public' => ['required', 'boolean'],
        ]);
        $data['headline'] = $data['headline'] ?? '';
        $profile = $request->user()->professionalProfile()->first();
        if (! $profile) {
            $profile = $request->user()->professionalProfile()->make();
            $profile->public_id = (string) Str::uuid();
        }
        $profile->fill($data); // No verification status or role can be mass assigned.
        $profile->save();
        return back()->with('success', 'Profiel opgeslagen. Jouw expertise staat als zelf opgegeven vermeld.');
    }
    public function index()
    {
        return Inertia::render('profiles', ['profiles' => ProfessionalProfile::where('is_public', true)->whereHas('user', fn ($q) => $q->role(['staff', 'admin']))->latest()->paginate(12, ['public_id', 'display_name', 'headline', 'specialties'])]);
    }
    public function show(string $publicId)
    {
        $profile = ProfessionalProfile::where('public_id', $publicId)->where('is_public', true)->whereHas('user', fn ($q) => $q->role(['staff', 'admin']))->firstOrFail();
        return Inertia::render('profile', ['profile' => $profile->only(['public_id', 'display_name', 'headline', 'biography', 'education', 'specialties', 'verification_status'])]);
    }
}
