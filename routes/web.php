<?php

use App\Http\Controllers\MediaController;
use App\Http\Middleware\EnsureActiveAdmin;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');
Route::get('/media-uploads/{path}', [MediaController::class, 'upload'])->where('path', '.*')
    ->middleware(['auth', EnsureActiveAdmin::class])->name('media.upload');
Route::get('/media/{path}', MediaController::class)->where('path', '.*')
    ->middleware(['auth', EnsureActiveAdmin::class])->name('media.file');
