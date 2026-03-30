<?php

use App\Http\Controllers\StudentScheduleController;
use Illuminate\Support\Facades\Route;


Route::post('/student/login',    [StudentScheduleController::class, 'login']);
Route::post('/student/schedule', [StudentScheduleController::class, 'getSchedule']);
Route::get('/student/{id}',      [StudentScheduleController::class, 'show']);
