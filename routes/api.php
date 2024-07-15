<?php

use App\Http\Controllers\AqiStationController;
use App\Http\Controllers\AqiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StationController;
use App\Models\AqiStation;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/route-cache', function () {
    $exitCode = Artisan::call('route:cache');
    $exitCode = Artisan::call('config:cache');
    $exitCode = Artisan::call('config:clear');
    $exitCode = Artisan::call('cache:clear');
    $exitCode = Artisan::call('view:clear');
    echo "optimized";
    return print_r($exitCode);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::prefix('air')->group(function () {
    Route::get('/get-all', [AqiStationController::class, 'getAll']); 
    Route::get('/aqi/{id}', [AqiController::class, 'getById']);
    Route::get('/get-most-polluted', [AqiStationController::class, 'getMostPolluted']); 
    Route::get('/update', [AqiStationController::class, 'update']);
    Route::get('/export-avg', [AqiController::class, 'exportAvg']);
    Route::get('/export-data-aqi/{id}', [AqiController::class, 'exportDataAqi']);
    Route::get('/overall', [AqiController::class, 'getOverall']); //home
    Route::get('/overall/{group}', [AqiController::class, 'getOverallGroup']); //home
});

Route::prefix('station')->group(function () {
    Route::get('/get-all', [StationController::class, 'index']);
});

Route::get('/anjay-keren', [AqiStationController::class, 'getCobaSatu']);
