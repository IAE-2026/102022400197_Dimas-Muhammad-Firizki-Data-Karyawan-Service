<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('federated_users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->string('sso_subject')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('nim')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('federated_users');
        Schema::dropIfExists('roles');
    }
};
