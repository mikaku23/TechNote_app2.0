<?php

use App\Http\Controllers\SoftwareController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::resource('software', SoftwareController::class);