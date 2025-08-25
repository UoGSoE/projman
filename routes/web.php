<?php

use Illuminate\Support\Facades\Route;

Route::get('/', \App\Livewire\HomePage::class)->name('home');
Route::get('/projects', \App\Livewire\ProjectList::class)->name('projects');
Route::get('/project/create', \App\Livewire\ProjectCreator::class)->name('project.create');
Route::get('/project/{project}', \App\Livewire\ProjectViewer::class)->name('project.show');
Route::get('/project/{project}/edit', \App\Livewire\ProjectEditor::class)->name('project.edit');
Route::get('/staff/heatmap', \App\Livewire\HeatMapViewer::class)->name('project.heatmap');
Route::get('/staff', \App\Livewire\UserList::class)->name('staff');
Route::get('/roles', \App\Livewire\RolesList::class)->name('roles');


