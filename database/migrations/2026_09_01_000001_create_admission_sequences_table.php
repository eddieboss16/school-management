<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per admission year, holding the next sequence number to issue.
     *
     * Admission numbers used to be derived by re-reading the highest existing
     * number on every insert, which made two simultaneous admissions compute
     * the same value. A counter row can be locked, so the number is claimed
     * before the student is written.
     */
    public function up(): void
    {
        Schema::create('admission_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->unsignedInteger('next_number')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_sequences');
    }
};
