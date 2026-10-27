<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Database v1.1 sections 10.1 to 10.4; UNIQUE (template_key,
     * version_number) per D-043 (Q-10).
     */
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->restrictOnDelete()->restrictOnUpdate();
            $table->string('signer_type', 10);
            $table->foreignId('signer_user_id')->nullable()->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->string('signer_name_snapshot');
            $table->timestamp('signed_at');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('signature_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('head_user_id')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->unsignedInteger('version_number');
            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->unique(['head_user_id', 'version_number']);
        });

        Schema::create('stamp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->restrictOnDelete()->restrictOnUpdate();
            $table->unsignedInteger('version_number')->unique();
            $table->timestamp('valid_from');
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('document_template_versions', function (Blueprint $table) {
            $table->id();
            $table->string('template_key', 50);
            $table->unsignedInteger('version_number');
            $table->string('renderer_key', 100);
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->unique(['template_key', 'version_number']);
            $table->index(['template_key', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_template_versions');
        Schema::dropIfExists('stamp_templates');
        Schema::dropIfExists('signature_templates');
        Schema::dropIfExists('signatures');
    }
};
