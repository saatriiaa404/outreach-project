<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Database v1.1 section 9.2. decided_at is the date printed on the
     * approval block (VAL-004).
     */
    public function up(): void
    {
        Schema::create('outreach_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outreach_id')->constrained('outreaches')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('outreach_version_id')->unique()->constrained('outreach_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('kepala_id')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->string('decision', 10);
            $table->text('note')->nullable();
            $table->foreignId('head_signature_template_id')->nullable()->constrained('signature_templates')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('stamp_template_id')->nullable()->constrained('stamp_templates')->restrictOnDelete()->restrictOnUpdate();
            $table->timestamp('decided_at');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outreach_approvals');
    }
};
