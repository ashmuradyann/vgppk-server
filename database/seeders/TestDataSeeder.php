<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Specialty;
use App\Models\StudentGroup;
use App\Models\Student;
use App\Models\AcademicYear;

class TestDataSeeder extends Seeder
{
    // public function run(): void
    // {
    //     // 1. Создаем учебный год
    //     // $year = AcademicYear::create([
    //     //     'label' => '2025/2026',
    //     //     'is_current' => true
    //     // ]);

    //     // // 2. Создаем специальность, привязанную к этому году
    //     // $specialty = Specialty::create([
    //     //     'code' => '09.02.07',
    //     //     'title' => 'Информационные системы и программирование',
    //     //     'academic_year_id' => $year->id
    //     // ]);

    //     // // 3. Создаем группу
    //     // $group = StudentGroup::create([
    //     //     'name' => 'ИСП-312',
    //     //     'academic_year' => '2025/2026',
    //     //     'specialty_id' => $specialty->id
    //     // ]);

    //     // // 4. Добавляем студентов
    //     // $students = [
    //     //     'Иванов Иван Иванович',
    //     //     'Петров Петр Петрович',
    //     //     'Сидоров Сидор Сидорович'
    //     // ];

    //     // foreach ($students as $name) {
    //     //     Student::create([
    //     //         'full_name' => $name,
    //     //         'student_group_id' => $group->id
    //     //     ]);
    //     // }
    // }
}