<?php

use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AuthController::class, 'register']);

// Cascading location lookups (Malaysia): country → states → cities → postcodes
Route::get('/states/{iso2?}', [LocationController::class, 'states']);
Route::get('/states/{state}/cities', [LocationController::class, 'cities']);
Route::get('/cities/{city}/postcodes', [LocationController::class, 'postcodes']);
