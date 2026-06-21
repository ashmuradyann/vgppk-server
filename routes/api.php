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

Route::put('/groups/{groupId}', [GroupController::class, 'updateGroup']);

Route::put('/groups/{groupId}/practices/{practiceId}', [GroupController::class, 'updatePractice']);

Route::delete('/groups/{groupId}/practices/{practiceId}', [GroupController::class, 'deletePractice']);

Route::post('/students/create', [StudentController::class, 'createStudent']);

Route::put('/students/update/{id}', [StudentController::class, 'setStudentPracticeData']);

Route::delete('/students/{id}', [StudentController::class, 'destroyStudent']);

Route::get('/specialties', [SpecialtyController::class, 'index']);

Route::post('/specialties', [SpecialtyController::class, 'store']);

Route::put('/specialties', [SpecialtyController::class, 'update']);

Route::delete('/specialties/{id}', [SpecialtyController::class, 'destroy']);

Route::get('/practice_bases', [PracticeBaseController::class, 'index']);

Route::post('/practice_bases', [PracticeBaseController::class, 'store']);

Route::put('/practice_bases', [PracticeBaseController::class, 'update']);

Route::delete('/practice_bases/{id}', [PracticeBaseController::class, 'destroy']);

Route::post('/student_characteristic', [DocumentExportController::class, 'getCharacteristicOfStudent']);

Route::post('/characteristic_group', [DocumentExportController::class, 'getCharacteristicOfGroupDocument']);

Route::post('/student_certificat_sheet', [DocumentExportController::class, 'getCertificatSheetDocument']);

Route::post('/certificat_sheet_group', [DocumentExportController::class, 'getCertificatSheetGroupDocument']);

Route::post('/agreement_document', [DocumentExportController::class, 'getAgreementDocument']);

Route::post('/review_document', [DocumentExportController::class, 'getReviewDocument']);

Route::post('/direction_document', [DocumentExportController::class, 'getDirectionDocument']);

Route::post('/direction_group_document', [DocumentExportController::class, 'getDirectionGroupDocument']);

Route::post('/ordering_document', [DocumentExportController::class, 'getOrderingDocument']);