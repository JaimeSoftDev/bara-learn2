<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutoring_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutoring_thread_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['tutoring_thread_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutoring_messages');
    }
};
