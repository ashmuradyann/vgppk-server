<?php

// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SpecialtyController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/groups', [GroupController::class, 'index']);

Route::get('/groups/{id}', [GroupController::class, 'show']);

Route::post('/groups/import', [GroupController::class, 'store']);

Route::delete('/groups/{id}', [GroupController::class, 'destroy']);

Route::post('/groups/addSpecialty', [GroupController::class, 'addSpecialty']);

Route::post('/students/create', [StudentController::class, 'createStudent']);

Route::delete('/students/{id}', [StudentController::class, 'destroyStudent']);

Route::get('/specialties', [SpecialtyController::class, 'index']);

Route::post('/specialties', [SpecialtyController::class, 'store']);

Route::delete('/specialties/{id}', [SpecialtyController::class, 'destroy']);
