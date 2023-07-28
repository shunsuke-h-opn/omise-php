<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChargeController;

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

Route::get('/', function () {
    return view('action-list');
});

Route::get('/create_charge', [ChargeController::class, 'index']);
Route::get('/check_charge', [ChargeController::class, 'checkCharge']);
Route::post('/create_charge', [ChargeController::class, 'cardCharge']);
