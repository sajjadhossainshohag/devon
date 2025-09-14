<?php

use App\Http\Controllers\ValetController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ValetController::class, 'dashboard'])->name('dashboard');
