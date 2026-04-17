<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // who did it
            $table->string('action');        // created, updated, deleted
            $table->string('model_type');    // Student, Teacher, etc.
            $table->unsignedBigInteger('model_id')->nullable();
            $table->string('description');   // human-readable line
            $table->json('changes')->nullable(); // before/after for updates
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
