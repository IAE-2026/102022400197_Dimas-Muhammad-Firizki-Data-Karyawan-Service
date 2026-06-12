<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('event_name')->nullable()->after('activity_name');
            $table->string('sso_subject')->nullable()->after('event_name');
            $table->string('status')->default('completed')->after('receipt_number');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropColumn(['event_name', 'sso_subject', 'status']);
        });
    }
};
