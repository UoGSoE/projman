<?php

use Illuminate\Support\Facades\Route;

require __DIR__.'/sso-auth.php';

Route::group(['middleware' => 'auth'], function () {
    Route::get('/', \App\Livewire\HomePage::class)->name('home');
    Route::get('/projects', \App\Livewire\ProjectList::class)->name('projects');
    Route::get('/project/create', \App\Livewire\ProjectCreator::class)->name('project.create');
    Route::get('/project/{project}', \App\Livewire\ProjectViewer::class)->name('project.show');
    Route::get('/project/{project}/edit', \App\Livewire\ProjectEditor::class)->name('project.edit');
    Route::get('/staff/heatmap', \App\Livewire\HeatMapViewer::class)->name('project.heatmap');


    Route::get('/profile', \App\Livewire\Profile::class)->name('profile');

    Route::middleware(['admin'])->group(function () {
        Route::get('/staff', \App\Livewire\UserList::class)->name('users.list');
        Route::get('/roles', \App\Livewire\RolesList::class)->name('roles.list');
        Route::get('/skills', \App\Livewire\SkillsManager::class)->name('skills.manage');
        Route::get('/user/{user}', \App\Livewire\UserViewer::class)->name('user.show');
        Route::get('/portfolio/backlog', \App\Livewire\BacklogList::class)->name('portfolio.backlog');
        Route::get('/portfolio/change-on-a-page/{project}', \App\Livewire\ChangeOnAPage::class)->name('portfolio.change-on-a-page');
        Route::get('/portfolio/roadmap', \App\Livewire\RoadmapView::class)->name('portfolio.roadmap');
    });
});
