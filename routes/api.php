<?php

use App\Http\Controllers\TagController;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskController;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return response()->json($request->user());
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('tasks', [TaskController::class, 'index']);
    Route::post('tasks', [TaskController::class, 'store']);
    Route::put('tasks/{id}', [TaskController::class, 'update']);
    Route::delete('tasks/{id}', [TaskController::class, 'destroy']);
    Route::get('tasks/{id}', [TaskController::class, 'show']);
    Route::patch('tasks/{id}/complete', [TaskController::class, 'markAsCompleted']);
    Route::patch('tasks/{id}/incomplete', [TaskController::class, 'markAsIncomplete']);

    Route::patch('tasks/{id}/archive', [TaskController::class, 'archive']);
    Route::patch('tasks/{id}/restore', [TaskController::class, 'restore']);

    Route::post('tags', [TagController::class, 'store']);
    Route::get('tags', [TagController::class, 'index']);
});


