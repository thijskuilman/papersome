<?php

use App\Http\Controllers\CoverImageController;
use App\Http\Controllers\OpdsController;
use Illuminate\Support\Facades\Route;

Route::get('/publication/{publication}/cover/generate', [CoverImageController::class, 'getTemplate'])
    ->name('cover.generate');

Route::get('/opds/{user}', [OpdsController::class, 'userPublications'])
    ->name('opds.user');
