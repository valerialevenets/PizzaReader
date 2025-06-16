<?php

use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\ChapterController;
use App\Http\Controllers\Admin\ComicController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// The "+" near the role means "which has this privilege or more" [admin > manager > editor > checker > user]

Route::prefix('admin')->middleware("log.request")->group(function () {
    Route::middleware("log.request")->group(function() {
        Auth::routes(['register' => (bool) config('settings.registration_enabled')]);
    });

    Route::name('admin.')->middleware('auth')->group(function () {

        Route::get('/', function () {
            return redirect()->route('admin.comics.index');
        })->name('index');

        // Only managers+ can create, store, edit, update, destroy and search comics
        Route::resource('comics', ComicController::class)->except(['index', 'show', 'createFromMangadex'])->middleware('auth.manager');
        Route::post('comics/search/{search}', [ComicController::class, 'search'])->name('search')->middleware('auth.manager');

        Route::name('comics.')->group(function () {
            // Only checkers+ can see list of chapter
            Route::get('comics', [ComicController::class, 'index'])->name('index')->middleware('auth.checker');
            Route::get('comics/createFromMangadex', [ComicController::class, 'createFromMangadex'])->name('createFromMangadex')->middleware('auth.manager');
            Route::prefix('comics/{comic}')->group(function () {
                // Authorized checkers+ can see a comic
                Route::get('', [ComicController::class, 'show'])->name('show')->middleware('can.see');

                // Authorized editors+ can create, store, edit and update chapters
                Route::resource('chapters', ChapterController::class)->except(['destroy', 'show', 'index'])->middleware('can.edit');

                // Authorized checkers+ can see the list of comic's chapters and his chapters
                Route::resource('chapters', ChapterController::class)->only(['show', 'index'])->middleware('can.see');

                // Only managers+ can destroy chapters
                Route::delete('chapters/{chapter}', [ChapterController::class, 'destroy'])->name('chapters.destroy')->middleware('auth.manager');

                // Only editors+ can store and destroy pages
                Route::resource('chapters/{chapter}/pages', PageController::class)->only(['store', 'destroy', 'index'])->names('chapters.pages')->middleware('can.edit');

                // Authorized checkers+ can see the stats
                Route::get('stats', [ComicController::class, 'stats'])->name('stats')->middleware('can.see');
            });
        });

        Route::prefix('users')->name('users.')->group(function(){
            Route::get('create', [UserController::class, 'create'])->name('create')->middleware('auth.admin');
            Route::post('create', [UserController::class, 'addUser'])->name('addUser')->middleware('auth.admin');
            Route::get('/', [UserController::class, 'index'])->name('index')->middleware('auth.manager');
            Route::redirect('/{user}', '/admin/users');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('auth.admin');
            Route::patch('/{user}/update', [UserController::class, 'update'])->name('update')->middleware('auth.admin');
            Route::delete('/{user}/destroy', [UserController::class, 'destroy'])->name('destroy')->middleware('auth.admin');
            Route::patch('/{user}/comics', [UserController::class, 'comics'])->name('comics')->middleware('auth.manager');
        });

        Route::prefix('settings')->name('settings.')->middleware('auth.admin')->group(function () {
            Route::get('/', [SettingsController::class, 'edit'])->name('edit');
            Route::patch('/', [SettingsController::class, 'update'])->name('update');
        });

        Route::name('teams.')->group(function(){
            Route::get('/teams', [TeamController::class, 'index'])->name('index')->middleware('auth.manager');
            Route::resource('teams', TeamController::class)->names('')->except(['index', 'show'])->middleware('auth.admin');
            Route::get('/teams/{team}', function () { return redirect()->route('admin.teams.index'); });
        });

    });
});

Route::prefix('user')->name('user.')->middleware('auth')->group(function() {
    Route::get('/edit', [UserController::class, 'editYourself'])->name('edit');
    Route::redirect('/', '/user/edit');
    Route::patch('/update', [UserController::class, 'updateYourself'])->name('update')->middleware("log.request");
});
