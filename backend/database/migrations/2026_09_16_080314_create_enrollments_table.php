<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            // How access was granted: paid online, paid in cash to the
            // teacher and recorded manually, or a free course.
            $table->enum('source', ['stripe', 'cash', 'free'])->default('free');
            // Teacher (or admin) who manually granted a cash enrollment.
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('price_paid_cents')->default(0);
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
