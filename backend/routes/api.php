<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InstallationPackageController;
use App\Http\Controllers\InstallationTicketController;
use App\Http\Controllers\SurveyResultController;
use App\Http\Controllers\WaterTariffBlockController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me',       [AuthController::class, 'me']);
});


Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/test-admin', fn() => response()->json(['message' => 'Kamu admin!']));

    Route::apiResource('installation-packages', InstallationPackageController::class);
    Route::apiResource('installation-packages.water-tariff-blocks', WaterTariffBlockController::class);

    Route::get('installation-tickets',  [InstallationTicketController::class, 'index']);
    Route::post('installation-tickets', [InstallationTicketController::class, 'store']);
    Route::get('installation-tickets/{installationTicket}', [InstallationTicketController::class, 'show']);
    Route::patch('installation-tickets/{installationTicket}/transition', [InstallationTicketController::class, 'transition']);


    // route admin lainnya...

});

Route::middleware(['auth:sanctum', 'role:surveyor'])->group(function () {
    Route::get('/test-surveyor', fn() => response()->json(['message' => 'Kamu surveyor!']));

    Route::post('installation-tickets/{installationTicket}/survey',[SurveyResultController::class, 'store'] );

    // route surveyor lainnya...

});

Route::middleware('role:teknisi')->group(function () {
    Route::get('/test-teknisi', fn() => response()->json(['message' => 'Kamu teknisi!']));

    // route teknisi lainnya...

});

Route::middleware('role:pelanggan')->group(function () {
    Route::get('/test-pelanggan', fn() => response()->json(['message' => 'Kamu pelanggan!']));

    // route pelanggan lainnya...

});
