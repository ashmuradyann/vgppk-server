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
        return response()->json([
            'data' => Specialty::all()
        ]);
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
}

?>