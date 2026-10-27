<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Database v1.1 sections 8.3 and 9.1. Rows are immutable once created;
     * snapshot_data content follows the contract approved in R6.1.
     */
    public function up(): void
    {
        Schema::create('outreach_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outreach_id')->constrained('outreaches')->restrictOnDelete()->restrictOnUpdate();
            $table->unsignedInteger('version_number');
            $table->string('status', 20);
            $table->json('snapshot_data');
            $table->foreignId('document_template_version_id')->constrained('document_template_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('mustahik_signature_id')->nullable()->constrained('signatures')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('kifayah_signature_id')->nullable()->constrained('signatures')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('finalized_at');

            $table->unique(['outreach_id', 'version_number']);
        });

        Schema::create('outreach_version_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outreach_version_id')->constrained('outreach_versions')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('file_id')->constrained('files')->restrictOnDelete()->restrictOnUpdate();
            $table->string('category', 20);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->unique(['outreach_version_id', 'file_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outreach_version_attachments');
        Schema::dropIfExists('outreach_versions');
    }
};
