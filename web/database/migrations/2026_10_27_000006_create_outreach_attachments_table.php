<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Database v1.1 section 8.2; soft-deleted relations are reactivated
     * instead of duplicated.
     */
    public function up(): void
    {
        Schema::create('outreach_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outreach_id')->constrained('outreaches')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('file_id')->constrained('files')->restrictOnDelete()->restrictOnUpdate();
            $table->string('category', 20);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['outreach_id', 'file_id']);
            $table->index(['outreach_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outreach_attachments');
    }
};
