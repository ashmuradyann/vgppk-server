<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('practices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('start_date');
            $table->string('end_date');
            $table->string('type');
            $table->integer('student_group_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practices');
    }
};