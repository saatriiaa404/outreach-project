<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Database v1.1 sections 6 and 7. Answers are single-choice JSON
     * {"option": "<code>"} per D-043 (Q-07).
     */
    public function up(): void
    {
        Schema::create('mustahik_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outreach_id')->unique()->constrained('outreaches')->restrictOnDelete()->restrictOnUpdate();
            $table->foreignId('petugas_id')->constrained('users')->restrictOnDelete()->restrictOnUpdate();
            $table->date('survey_date')->nullable();
            $table->string('kepala_keluarga_nama')->nullable();
            $table->string('kepala_keluarga_nik_no_kk', 32)->nullable();
            $table->text('kepala_keluarga_alamat')->nullable();
            $table->string('kepala_keluarga_rt', 5)->nullable();
            $table->string('kepala_keluarga_rw', 5)->nullable();
            $table->foreignId('signature_id')->nullable()->constrained('signatures')->restrictOnDelete()->restrictOnUpdate();
            $table->timestamps();
        });

        Schema::create('mustahik_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mustahik_form_id')->constrained('mustahik_forms')->restrictOnDelete()->restrictOnUpdate();
            $table->string('question_code', 50);
            $table->json('answer');
            $table->timestamps();

            $table->unique(['mustahik_form_id', 'question_code']);
        });

        Schema::create('kifayah_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outreach_id')->unique()->constrained('outreaches')->restrictOnDelete()->restrictOnUpdate();
            $table->unsignedBigInteger('income_head_family')->nullable();
            $table->unsignedBigInteger('income_other_members')->nullable();
            foreach ([
                'head_household', 'housewife', 'adult_working', 'adult_not_working',
                'school_sma', 'school_smp', 'school_sd', 'baby_not_school',
                'disability', 'breastfeeding', 'pregnant', 'widow_not_working',
            ] as $expense) {
                $table->unsignedBigInteger('expense_'.$expense)->nullable();
            }
            $table->unsignedSmallInteger('higher_education_count')->nullable();
            $table->foreignId('signature_id')->nullable()->constrained('signatures')->restrictOnDelete()->restrictOnUpdate();
            $table->timestamps();
        });

        Schema::create('kifayah_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kifayah_form_id')->constrained('kifayah_forms')->restrictOnDelete()->restrictOnUpdate();
            $table->string('question_code', 50);
            $table->json('answer');
            $table->timestamps();

            $table->unique(['kifayah_form_id', 'question_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kifayah_answers');
        Schema::dropIfExists('kifayah_forms');
        Schema::dropIfExists('mustahik_answers');
        Schema::dropIfExists('mustahik_forms');
    }
};
