<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('programs', fn (Blueprint $t) => $t->text('review_note')->nullable());
        Schema::table('enrollments', function (Blueprint $t) {
            $t->boolean('is_paused')->default(false);
            $t->timestamp('completed_at')->nullable();
        });
        Schema::create('lesson_responses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('lesson_index');
            $t->text('answer'); // Application-encrypted. Never add to a public search index.
            $t->timestamp('shared_at')->nullable();
            $t->text('feedback')->nullable(); // Application-encrypted; no free access for admins.
            $t->timestamp('feedback_at')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['enrollment_id', 'lesson_index']);
        });
        Schema::create('program_mails', function (Blueprint $t) {
            $t->id();
            $t->string('event_key', 180)->unique();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('kind', 40);
            $t->unsignedBigInteger('subject_id');
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_mails');
        Schema::dropIfExists('lesson_responses');
        Schema::table('enrollments', fn (Blueprint $t) => $t->dropColumn(['is_paused', 'completed_at']));
        Schema::table('programs', fn (Blueprint $t) => $t->dropColumn('review_note'));
    }
};
