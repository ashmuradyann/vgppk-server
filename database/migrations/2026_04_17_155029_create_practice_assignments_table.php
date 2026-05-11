<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('practice_assignments', function (Blueprint $table) {
            $table->id();
            // Связь с конкретной практикой (где уже есть даты, ПМ и компетенции)
            $table->foreignId('practice_id')->constrained()->onDelete('cascade');
            // Связь со студентом
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            // Связь с базой практики (организацией)
            $table->foreignId('practice_base_id')->constrained()->onDelete('cascade');

            // Индивидуальный руководитель от колледжа (если он отличается от общего)
            $table->string('college_teacher_name')->nullable();
            $table->string('college_teacher_position')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_assignments');
    }
};
