<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Resources\GroupResource;

use App\Models\Practice;
use App\Models\StudentGroup;
use App\Models\Student;

use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        $groups = StudentGroup::select('id', 'name', 'teacher_name')->get();

        return $groups;
    }

    public function show($id)
    {
        $group = StudentGroup::with(['specialty', 'students', 'practices'])->findOrFail($id);
        return $group;
    }

    public function store(Request $request)
    {
        $group = StudentGroup::create([
            'name' => $request->name,
            'teacher_name' => $request->teacher_name,
            'academic_year' => $request->academic_year,
        ]);

        foreach ($request->students as $fullName) {
            Student::create([
                'full_name' => $fullName,
                'student_group_id' => $group->id
            ]);
        }

        return response()->json([
            'group_id' => $group->id,
            'group_name' => $group->name,
            'teacher_name' => $group->teacher_name,
            "students" => $group->students
        ]);
    }

    public function destroy($id)
    {
        $group = StudentGroup::findOrFail($id);
        StudentGroup::destroy($id);
        return response()->json([
            'success' => true,
            'message' => 'Group deleted successfully',
            'name' => $group->name
        ], 200);
    }

    public function addSpecialty(Request $request)
    {
        $group = StudentGroup::findOrFail($request->group_id);
        $group->specialty_id = $request->specialty_id;
        $group->save();
        return response()->json([
            'success' => true
        ]);
    }

    public function addPractice(Request $request, $groupId)
    {
        $request->validate([
            'name' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'type' => 'required|string'
        ]);

        $group = StudentGroup::findOrFail($groupId);

        // Теперь Laravel знает о модели Practice
        $practice = Practice::create([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'type' => $request->type,
            'student_group_id' => $group->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Practice added successfully',
            'practice' => $practice
        ], 201);
    }

    // НОВЫЙ МЕТОД: получить все практики группы
    public function getPractices($groupId)
    {
        $group = StudentGroup::findOrFail($groupId);
        $practices = $group->practices()->get();

        return response()->json([
            'group_id' => $groupId,
            'group_name' => $group->name,
            'practices' => $practices
        ]);
    }

    // НОВЫЙ МЕТОД: удалить практику
    public function deletePractice($groupId, $practiceId)
    {
        $practice = Practice::where('student_group_id', $groupId)
            ->where('id', $practiceId)
            ->firstOrFail();

        $practice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Practice deleted successfully',
        ]);
    }

    // НОВЫЙ МЕТОД: обновить практику
    public function updatePractice(Request $request, $groupId, $practiceId)
    {
        $practice = Practice::where('student_group_id', $groupId)
            ->where('id', $practiceId)
            ->firstOrFail();

        $practice->update($request->only(['name', 'start_date', 'end_date', 'type']));

        return response()->json([
            'success' => true,
            'message' => 'Practice updated successfully',
            'practice' => $practice
        ]);
    }
}
