<?php

use App\Http\Controllers\Api\WorkpackageDeploymentController;
use App\Http\Controllers\Api\WorkpackageIndexController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::get('/workpackages', WorkpackageIndexController::class)->name('api.workpackages.index');
    Route::post('/workpackages/{project}/deployment', WorkpackageDeploymentController::class)
        ->name('api.workpackages.deployment.update');
});
