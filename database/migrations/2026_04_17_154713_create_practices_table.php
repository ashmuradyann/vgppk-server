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
        Schema::create('practices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_group_id')->constrained();
            $table->enum('type', ['УП', 'ПП', 'ПДП']); // Виды практик
            $table->string('pm_name'); // Наименование ПМ
            $table->date('start_date');
            $table->date('end_date');
            $table->text('work_types'); // Виды работ
            $table->json('ok_list');    // Общие компетенции
            $table->json('pk_list');    // Проф. компетенции
            $table->string('teacher_full_name');
            $table->string('teacher_position');
            $table->boolean('is_ready')->default(false); // Статус готовности (0 или 1)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practices');
    }
};
