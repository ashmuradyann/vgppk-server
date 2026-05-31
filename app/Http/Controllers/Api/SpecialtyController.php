<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Specialty;
use Illuminate\Http\Request;

class SpecialtyController extends Controller
{
    // Получить все специальности
    public function index()
    {
        $specialties = Specialty::select('id', 'code', 'specialty', "qualification")->get();

        return $specialties;
    }

    // Создать новую специальность
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:specialties',
            "specialty" => "required|string",
            "qualification" => "required|string"
        ]);

        $specialty = Specialty::create($validated);

        return response()->json([
            'message' => 'Специальность создана',
            'data' => $specialty
        ], 201);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:specialties,id',
            'code' => 'required|string|max:255',
            'specialty' => 'nullable|string|max:500',
            'qualification' => 'required|string|max:500',
        ]);

        $specialty = Specialty::findOrFail($validated['id']);

        $specialty->update($validated);

        return response()->json([
            'success' => true,
            'data' => $specialty
        ], 200);
    }

    public function destroy($id)
    {
        $specialty = Specialty::find($id);
        Specialty::destroy($id);
        return response()->json([
            'success' => true,
            'message' => 'Specialty deleted successfully',
            'name' => $specialty->specialty
        ], 200);
    }
}

?>