<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('staff_invitations', function (Blueprint $t) {
            $t->id(); $t->string('email')->index(); $t->string('role', 30); $t->string('token_hash', 64)->unique();
            $t->foreignId('invited_by')->constrained('users')->restrictOnDelete();
            $t->timestamp('expires_at'); $t->timestamp('accepted_at')->nullable(); $t->timestamp('revoked_at')->nullable();
            $t->foreignId('accepted_by')->nullable()->constrained('users')->nullOnDelete(); $t->timestamps();
        });
        Schema::create('professional_profiles', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); $t->uuid('public_id')->unique();
            $t->string('display_name')->default(''); $t->string('headline')->default(''); $t->text('biography')->nullable();
            $t->json('specialties')->nullable(); $t->text('education')->nullable();
            $t->string('verification_status', 30)->default('unreviewed'); $t->boolean('is_public')->default(false); $t->timestamps();
        });
        Schema::create('programs', function (Blueprint $t) {
            $t->id(); $t->foreignId('author_id')->constrained('users')->restrictOnDelete();
            $t->string('title', 160); $t->text('summary'); $t->text('goals')->nullable(); $t->unsignedSmallInteger('estimated_minutes')->default(15);
            $t->string('status', 20)->default('draft'); $t->json('lessons'); $t->timestamps();
        });
        Schema::create('program_versions', function (Blueprint $t) {
            $t->id(); $t->foreignId('program_id')->constrained()->restrictOnDelete(); $t->unsignedInteger('number');
            $t->string('title', 160); $t->text('summary'); $t->text('goals')->nullable(); $t->unsignedSmallInteger('estimated_minutes');
            $t->json('lessons'); $t->foreignId('published_by')->constrained('users')->restrictOnDelete(); $t->timestamps();
            $t->unique(['program_id', 'number']);
        });
        Schema::create('enrollments', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained()->cascadeOnDelete(); $t->foreignId('program_id')->constrained()->restrictOnDelete();
            $t->foreignId('program_version_id')->constrained()->restrictOnDelete(); $t->json('completed_lessons'); $t->timestamps();
            $t->unique(['user_id', 'program_id']);
        });
        Schema::create('audit_events', function (Blueprint $t) {
            $t->id(); $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('action', 100); $t->unsignedBigInteger('subject_id')->nullable(); $t->timestamps();
        });
    }
    public function down(): void
    {
        foreach (['audit_events', 'enrollments', 'program_versions', 'programs', 'professional_profiles', 'staff_invitations'] as $name) Schema::dropIfExists($name);
    }
};
