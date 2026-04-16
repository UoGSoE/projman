<?php

use App\Http\Controllers\Api\ProjectIndexController;
use App\Http\Controllers\Api\SkillIndexController;
use App\Http\Controllers\Api\SkillsGapController;
use App\Http\Controllers\Api\SkillUsersController;
use App\Http\Controllers\Api\UserIndexController;
use App\Http\Controllers\Api\UserSkillsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/ping', fn () => ['ok' => true])->name('api.ping');
    Route::get('/skills', SkillIndexController::class)->name('api.skills.index');
    Route::get('/skills/{skill}/users', SkillUsersController::class)->name('api.skills.users');
    Route::get('/users', UserIndexController::class)->name('api.users.index');
    Route::get('/users/{user}/skills', UserSkillsController::class)->name('api.users.skills');
    Route::get('/projects', ProjectIndexController::class)->name('api.projects.index');
    Route::get('/stats/skills-gap', SkillsGapController::class)->name('api.stats.skills-gap');
});
