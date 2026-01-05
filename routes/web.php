<?php

use App\Http\Controllers\CoverImageController;
use Illuminate\Support\Facades\Route;

Route::get('/publication/{publication}/cover/generate', [CoverImageController::class, 'getTemplate'])
    ->name('cover.generate');
