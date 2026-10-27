<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Database v1.1 sections 11 and 12. History and audit are append-only;
     * audit targets are not polymorphic foreign keys. The notifications table
     * is the application's own in-app table, not Laravel's database channel.
     */
    public function up(): void
    {
        Schema::create('history_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outreach_id')->constrained('outreaches')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->string('actor_name_snapshot');
            $table->string('actor_role_snapshot', 10);
            $table->string('event_type', 40);
            $table->foreignId('outreach_version_id')->nullable()->constrained('outreach_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('approval_id')->nullable()->constrained('outreach_approvals')->restrictOnDelete()->restrictOnUpdate();
            $table->text('description')->nullable();
            $table->text('reason')->nullable();
            $table->json('field_changes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['outreach_id', 'created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->string('action', 100);
            $table->string('target_type', 50);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['target_type', 'target_id']);
            $table->index('created_at');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->string('type', 30);
            $table->string('title');
            $table->text('message');
            $table->foreignId('outreach_id')->constrained('outreaches')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('outreach_version_id')->constrained('outreach_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('approval_id')->nullable()->constrained('outreach_approvals')->restrictOnDelete()->restrictOnUpdate();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'read_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('history_events');
    }
};
