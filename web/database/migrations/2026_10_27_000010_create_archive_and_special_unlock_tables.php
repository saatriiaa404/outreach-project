<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Database v1.1 sections 13.1 and 13.2.
     */
    public function up(): void
    {
        Schema::create('archive_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outreach_id')->constrained('outreaches')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('outreach_version_id')->constrained('outreach_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->string('external_system', 100);
            $table->string('archive_reference')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('special_unlock_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outreach_id')->constrained('outreaches')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->string('requester_role_snapshot', 10);
            $table->text('reason');
            $table->string('status', 15)->default('PENDING');
            $table->foreignId('admin_id')->nullable()->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->text('processed_reason')->nullable();
            $table->timestamps();
            $table->timestamp('processed_at')->nullable();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_unlock_requests');
        Schema::dropIfExists('archive_records');
    }
};
