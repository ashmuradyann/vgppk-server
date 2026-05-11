<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use App\Http\Resources\StudentResource;

use App\Models\Student;

use App\Models\StudentGroup;
use Illuminate\Http\Request;

class StudentController extends Controller
{
  public function createStudent(Request $request)
  {
    $student = Student::create([
      'full_name' => $request->name,
      'student_group_id' => $request->group_id
    ]);

    return response()->json([
      "id" => $student->id,
      'group_id' => $student->student_group_id,
      'full_name' => $student->full_name,
      'teacher_name' => StudentGroup::find($student->student_group_id)?->teacher_name
    ]);
  }

  public function destroyStudent($id)
  {
    $student = Student::findOrFail($id);
    $student->delete();

    return response()->json([
      'name' => $student->full_name
    ]);
  }
}
