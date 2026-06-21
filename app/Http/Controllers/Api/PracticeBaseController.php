<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PracticeBase;
use App\Models\Specialty;
use Illuminate\Http\Request;

class PracticeBaseController extends Controller
{
    // Получить все специальности
    public function index()
    {

        $practiceBases = PracticeBase::select('id', 'organisation', 'supervisors')->get();

        return $practiceBases;
    }

    // Создать новую специальность
    public function store(Request $request)
    {
        $validated = $request->validate([
            'organisation' => 'required|string',
            "supervisors" => "required|string",
        ]);

        $practice_base = PracticeBase::create($validated);

        return response()->json([
            'message' => 'База практики создана',
            'data' => $practice_base
        ], 201);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:practice_bases,id',
            'organisation' => 'required|string|max:255',
            'supervisors' => 'nullable|string|max:500',
        ]);

        $practiseBase = PracticeBase::findOrFail($validated['id']);

        $practiseBase->update($validated);

        return response()->json([
            'success' => true,
            'data' => $practiseBase
        ], 200);
    }
    public function destroy($id)
    {
        $practiseBase = PracticeBase::find($id);
        PracticeBase::destroy($id);
        return response()->json([
            'success' => true,
            'message' => 'Specialty deleted successfully',
            'name' => $practiseBase->organisation
        ], 200);
    }
}

?>