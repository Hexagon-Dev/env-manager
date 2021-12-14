<?php

use App\Http\Controllers\EnvironmentController;
use Illuminate\Support\Facades\Route;

Route::get('/{project}/{branch}', [EnvironmentController::class, 'show']);
Route::post('/{project}/{branch}', [EnvironmentController::class, 'save']);
