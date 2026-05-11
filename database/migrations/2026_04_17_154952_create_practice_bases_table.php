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
        Schema::create('practice_bases', function (Blueprint $table) {
            $table->id();
            $table->string('name');             // Полное наименование организации
            $table->string('director_name');     // ФИО руководителя организации
            $table->string('director_position'); // Должность руководителя
            $table->string('address')->nullable(); // Можно добавить адрес для документов
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_bases');
    }
};
