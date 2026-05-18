<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Resources\GroupResource;

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
        $group = StudentGroup::with(['specialty', 'students'])->findOrFail($id);
        return new GroupResource($group);
    }

    public function store(Request $request)
    {
        $group = StudentGroup::create([
            'name' => $request->name,
            'course' => $request->course,
            'teacher_name' => $request->teacher_name,
            'academic_year' => $request->academic_year,
            // "specialty" => $request->specialty,
            // "practise_type" => $request->practise_type
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
}
