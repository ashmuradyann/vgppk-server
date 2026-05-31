<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpWord\TemplateProcessor;

class DocumentExportController extends Controller
{
    public function getCharacteristicOfStudent(Request $request)
    {
        $validated = $request->validate([
            'student_name' => 'required|string',
            'group_number' => 'required|string',
            'specialty' => 'required|string',
            'qualification' => 'required|string'
        ]);

        $templatePath = storage_path('app/templates/Kharakteristika_professionalnoy_deyatelnosti.docx');
        $templateProcessor = new TemplateProcessor($templatePath);

        $templateProcessor->setValue('student_name', $validated['student_name']);
        $templateProcessor->setValue('group_number', $validated['group_number']);
        $templateProcessor->setValue('specialty', $validated['specialty']);
        $templateProcessor->setValue('qualification', $validated['qualification']);

        $fileName = 'Kharakteristika_' . $validated['student_name'] . '.docx';
        $tempFile = storage_path('app/temp/' . $fileName);

        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }
}
