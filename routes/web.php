<?php

use App\Http\Controllers\ProjectExportController;
use App\Livewire\BacklogList;
use App\Livewire\ChangeOnAPage;
use App\Livewire\HeatMapViewer;
use App\Livewire\HomePage;
use App\Livewire\Profile;
use App\Livewire\ProjectCreator;
use App\Livewire\ProjectEditor;
use App\Livewire\ProjectList;
use App\Livewire\ProjectViewer;
use App\Livewire\RoadmapView;
use App\Livewire\RolesList;
use App\Livewire\Settings;
use App\Livewire\SkillsImporter;
use App\Livewire\SkillsManager;
use App\Livewire\UserList;
use App\Livewire\UserViewer;
use Illuminate\Support\Facades\Route;

require __DIR__.'/sso-auth.php';

Route::group(['middleware' => 'auth'], function () {
    Route::get('/', HomePage::class)->name('home');
    Route::get('/work-package/create', ProjectCreator::class)->name('project.create');
    Route::get('/work-package/{project}', ProjectViewer::class)->name('project.show');
    Route::get('/work-package/{project}/edit', ProjectEditor::class)->name('project.edit');

    Route::get('/profile', Profile::class)->name('profile');

    Route::middleware(['admin'])->group(function () {
        Route::get('/work-packages', ProjectList::class)->name('projects');
        Route::get('/staff', UserList::class)->name('users.list');
        Route::get('/staff/heatmap', HeatMapViewer::class)->name('project.heatmap');
        Route::get('/roles', RolesList::class)->name('roles.list');
        Route::get('/skills', SkillsManager::class)->name('skills.manage');
        Route::get('/skills/import', SkillsImporter::class)->name('skills.import');
        Route::get('/user/{user}', UserViewer::class)->name('user.show');
        Route::get('/portfolio/backlog', BacklogList::class)->name('portfolio.backlog');
        Route::get('/portfolio/change-on-a-page/{project}', ChangeOnAPage::class)->name('portfolio.change-on-a-page');
        Route::get('/portfolio/roadmap', RoadmapView::class)->name('portfolio.roadmap');
        Route::get('/work-package/{project}/export', ProjectExportController::class)->name('project.export');
        Route::get('/settings', Settings::class)->name('settings');
    });
});
