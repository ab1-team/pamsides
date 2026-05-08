<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Import Semua Controller
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ActivationController;
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
use App\Http\Controllers\SettingController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Semua Role)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me',       [AuthController::class, 'me']);

    // Route Settings (Bisa diakses semua role untuk dropdown form)
    Route::get('settings/kecamatan', [SettingController::class, 'getKecamatan']);
    Route::get('settings/desa', [SettingController::class, 'getDesa']);
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/test-admin', fn() => response()->json(['message' => 'Kamu admin!']));
    
    // Master Customers
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']); // Untuk Registrasi Pelanggan Awal

    // Master Packages & Tariffs
    Route::apiResource('installation-packages', InstallationPackageController::class);
    Route::apiResource('installation-packages.water-tariff-blocks', WaterTariffBlockController::class);

    // Installation Tickets Management
    Route::get('installation-tickets', [InstallationTicketController::class, 'index']);
    Route::put('installation-tickets/{id}/register', [InstallationTicketController::class, 'registerInstallation']);
    Route::get('installation-tickets/{installationTicket}', [InstallationTicketController::class, 'show']);
    Route::patch('installation-tickets/{installationTicket}/transition', [InstallationTicketController::class, 'transition']);
    Route::post('installation-tickets/{installationTicket}/payment', [PaymentController::class, 'store']);
    Route::post('installation-tickets/{installationTicket}/activate', [ActivationController::class, 'activate']);
    
    // Billing & Invoices
    Route::get('bills/recap',         [BillingController::class, 'recap']);
    Route::post('bills/generate',     [BillingController::class, 'generate']);
    Route::get('bills/{monthlyBill}', [BillingController::class, 'show']);

    Route::get('monthly-bills', [MonthlyBillController::class, 'index']);
    Route::post('monthly-bills/{id}/pay', [MonthlyBillController::class, 'pay']);
    Route::post('monthly-bills/generate', [MonthlyBillController::class, 'generate']);

    // Dashboard & Statistics
    Route::get('dashboard/statistics', [DashboardController::class, 'statistics']);

    // Reports Management
    Route::get('reports/installations',           [InstallationTicketController::class, 'report']);
    Route::get('reports/bills',                   [MonthlyBillController::class, 'report']);
    Route::get('reports/billing',                 [ReportController::class, 'billing']);
    Route::get('reports/installation',            [ReportController::class, 'installation']);
    Route::get('reports/billing/export-csv',      [ReportController::class, 'exportBillingCsv']);
    Route::get('reports/billing/export-pdf',      [ReportController::class, 'exportBillingPdf']);
    Route::get('reports/installation/export-csv', [ReportController::class, 'exportInstallationCsv']);
    Route::get('reports/installation/export-pdf', [ReportController::class, 'exportInstallationPdf']);
});

/*
|--------------------------------------------------------------------------
| Surveyor Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:surveyor'])->group(function () {
    Route::get('/test-surveyor', fn() => response()->json(['message' => 'Kamu surveyor!']));
    Route::post('installation-tickets/{installationTicket}/survey', [SurveyResultController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Teknisi Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:teknisi'])->group(function () {
    Route::get('/test-teknisi', fn() => response()->json(['message' => 'Kamu teknisi!']));
    
    Route::post('installation-tickets/{installationTicket}/installation-result', [InstallationResultController::class, 'store']);
    Route::get('meter-readings/pending', [MeterReadingController::class, 'index']);
    Route::post('meter-readings', [MeterReadingController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Pelanggan Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:pelanggan'])->group(function () {
    Route::get('/test-pelanggan', fn() => response()->json(['message' => 'Kamu pelanggan!']));
    // Tambahkan route khusus pelanggan di sini (misal: cek tagihan mandiri)
});
