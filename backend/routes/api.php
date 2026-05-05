<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActivationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\InstallationPackageController;
use App\Http\Controllers\InstallationResultController;
use App\Http\Controllers\InstallationTicketController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SurveyResultController;
use App\Http\Controllers\WaterTariffBlockController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\MonthlyBillController;
use App\Http\Controllers\PelangganPortalController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me',       [AuthController::class, 'me']);
});


Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/test-admin', fn() => response()->json(['message' => 'Kamu admin!']));
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::apiResource('installation-packages', InstallationPackageController::class);
    Route::apiResource('installation-packages.water-tariff-blocks', WaterTariffBlockController::class);

    Route::get('installation-tickets',  [InstallationTicketController::class, 'index']);
    Route::post('installation-tickets', [InstallationTicketController::class, 'store']);
    Route::get('reports/installations', [InstallationTicketController::class, 'report']);
    Route::get('installation-tickets/{installationTicket}', [InstallationTicketController::class, 'show']);
    Route::patch('installation-tickets/{installationTicket}/transition', [InstallationTicketController::class, 'transition']);
    Route::post('installation-tickets/{installationTicket}/payment',[PaymentController::class, 'store']);
    Route::post('installation-tickets/{installationTicket}/activate',[ActivationController::class, 'activate']);
    
    Route::get('bills/recap',         [BillingController::class, 'recap']);
    Route::post('bills/generate',     [BillingController::class, 'generate']);
    Route::get('bills/{monthlyBill}', [BillingController::class, 'show']);

    Route::get('dashboard/statistics', [DashboardController::class, 'statistics']);


    Route::get('reports/billing',                  [ReportController::class, 'billing']);
    Route::get('reports/installation',             [ReportController::class, 'installation']);
    Route::get('reports/billing/export-csv',       [ReportController::class, 'exportBillingCsv']);
    Route::get('reports/billing/export-pdf',       [ReportController::class, 'exportBillingPdf']);
    Route::get('reports/installation/export-csv',  [ReportController::class, 'exportInstallationCsv']);
    Route::get('reports/installation/export-pdf',  [ReportController::class, 'exportInstallationPdf']);

    Route::get('monthly-bills', [MonthlyBillController::class, 'index']);
    Route::post('monthly-bills/{id}/pay', [MonthlyBillController::class, 'pay']);
    Route::post('monthly-bills/generate', [MonthlyBillController::class, 'generate']);
    Route::get('reports/bills', [MonthlyBillController::class, 'report']);
    
    // route admin lainnya...

});

Route::middleware(['auth:sanctum', 'role:surveyor'])->group(function () {
    Route::get('/test-surveyor', fn() => response()->json(['message' => 'Kamu surveyor!']));

    Route::post('installation-tickets/{installationTicket}/survey',[SurveyResultController::class, 'store'] );

    // route surveyor lainnya...

});

Route::middleware(['auth:sanctum', 'role:teknisi'])->group(function () {
    Route::get('/test-teknisi', fn() => response()->json(['message' => 'Kamu teknisi!']));
    
    Route::post('installation-tickets/{installationTicket}/installation-result',[InstallationResultController::class, 'store']);
    Route::get('meter-readings/pending', [MeterReadingController::class, 'index']);
    Route::post('meter-readings', [MeterReadingController::class, 'store']);
    // route teknisi lainnya...

});

Route::middleware(['auth:sanctum', 'role:pelanggan'])->group(function () {
    Route::get('/test-pelanggan', fn() => response()->json(['message' => 'Kamu pelanggan!']));

    Route::get('/pelanggan/dashboard', [PelangganPortalController::class, 'dashboard']);
    Route::get('/pelanggan/bill-detail/{id?}', [PelangganPortalController::class, 'billDetail']);
    Route::get('/pelanggan/bill-history', [PelangganPortalController::class, 'billHistory']);
    Route::get('/pelanggan/profile',   [PelangganPortalController::class, 'profile']);
});
