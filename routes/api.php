<?php

// use Illuminate\Http\Request;
use App\Models\PracticeBase;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SpecialtyController;
use App\Http\Controllers\Api\PracticeBaseController;
use App\Http\Controllers\Api\DocumentExportController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/groups', [GroupController::class, 'index']);

Route::get('/groups/{id}', [GroupController::class, 'show']);

Route::post('/groups/import', [GroupController::class, 'store']);

Route::delete('/groups/{id}', [GroupController::class, 'destroy']);

Route::post('/groups/addSpecialty', [GroupController::class, 'addSpecialty']);

Route::post('/groups/{groupId}/practices', [GroupController::class, 'addPractice']);

// Route::get('/groups/{groupId}/practices', [GroupController::class, 'getPractices']);

Route::put('/groups/{groupId}/practices/{practiceId}', [GroupController::class, 'updatePractice']);

Route::delete('/groups/{groupId}/practices/{practiceId}', [GroupController::class, 'deletePractice']);

Route::post('/students/create', [StudentController::class, 'createStudent']);

Route::delete('/students/{id}', [StudentController::class, 'destroyStudent']);

Route::get('/specialties', [SpecialtyController::class, 'index']);

Route::post('/specialties', [SpecialtyController::class, 'store']);

Route::put('/specialties', [SpecialtyController::class, 'update']);

Route::delete('/specialties/{id}', [SpecialtyController::class, 'destroy']);

Route::get('/practice_bases', [PracticeBaseController::class, 'index']);

Route::post('/practice_bases', [PracticeBaseController::class, 'store']);

Route::put('/practice_bases', [PracticeBaseController::class, 'update']);

Route::delete('/practice_bases/{id}', [PracticeBaseController::class, 'destroy']);

Route::post('/student_documents', [DocumentExportController::class, 'getCharacteristicOfStudent']);