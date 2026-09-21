<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Spatie's default table names; teams are deliberately disabled in this first slice.
return new class extends Migration {
    public function up(): void
    {
        foreach (['permissions', 'roles'] as $name) {
            Schema::create($name, function (Blueprint $t) {
                $t->id(); $t->string('name', 125); $t->string('guard_name', 125); $t->timestamps(); $t->unique(['name', 'guard_name']);
            });
        }
        foreach (['permissions' => 'permission', 'roles' => 'role'] as $plural => $singular) {
            Schema::create('model_has_'.$plural, function (Blueprint $t) use ($plural, $singular) {
                $t->unsignedBigInteger($singular.'_id'); $t->string('model_type', 125); $t->unsignedBigInteger('model_id');
                $t->index(['model_id', 'model_type']);
                $t->foreign($singular.'_id')->references('id')->on($plural)->cascadeOnDelete();
                $t->primary([$singular.'_id', 'model_id', 'model_type']);
            });
        }
        Schema::create('role_has_permissions', function (Blueprint $t) {
            $t->foreignId('permission_id')->constrained()->cascadeOnDelete(); $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->primary(['permission_id', 'role_id']);
        });
    }
    public function down(): void
    {
        foreach (['role_has_permissions', 'model_has_roles', 'model_has_permissions', 'roles', 'permissions'] as $name) Schema::dropIfExists($name);
    }
};
