<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Central as CentralControllers;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::view('/', 'central.landing')->name('central.landing');

Route::get('/register', [CentralControllers\RegisterTenantController::class, 'show'])->name('central.tenants.register');
Route::post('/register/submit', [CentralControllers\RegisterTenantController::class, 'submit'])->name('central.tenants.register.submit');

Route::get('/login', [CentralControllers\LoginTenantController::class, 'show'])->name('central.tenants.login');
Route::post('/login/submit', [CentralControllers\LoginTenantController::class, 'submit'])->name('central.tenants.login.submit');



