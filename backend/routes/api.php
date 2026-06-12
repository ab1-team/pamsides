<?php

use App\Http\Controllers\ActivationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallationPackageController;
use App\Http\Controllers\InstallationResultController;
use App\Http\Controllers\InstallationTicketController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\MonthlyBillController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PelangganPortalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SopController;
use App\Http\Controllers\SurveyResultController;
use App\Http\Controllers\TroubleReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VillageController;
use App\Http\Controllers\WaterTariffBlockController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);
Route::get('/health', function () {
    return response()->json(['status' => 'OK']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes (Semua Role)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateProfile']);
    Route::put('/me/password', [AuthController::class, 'updatePassword']);
    Route::post('/me/avatar', [AuthController::class, 'uploadAvatar']);

    Route::get('settings/kecamatan', [SettingController::class, 'getKecamatan']);
    Route::get('settings/desa', [SettingController::class, 'getDesa']);
});

/*
|--------------------------------------------------------------------------
| Shared Routes (Admin, Surveyor, Teknisi)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin,surveyor,teknisi'])->group(function () {
    Route::get('installation-tickets', [InstallationTicketController::class, 'index']);
    Route::get('installation-tickets/{installationTicket}', [InstallationTicketController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Shared Routes (Admin & Teknisi)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin,teknisi'])->group(function () {
    Route::get('meter-readings/completed', [MeterReadingController::class, 'completed']);
    Route::get('dashboard/statistics', [DashboardController::class, 'statistics']);

    Route::get('meter-readings/pending', [MeterReadingController::class, 'index']);
    Route::post('meter-readings', [MeterReadingController::class, 'store']);
    Route::get('meter-readings/{id}', [MeterReadingController::class, 'show']);
    Route::put('meter-readings/{id}', [MeterReadingController::class, 'update']);
    Route::delete('meter-readings/{id}', [MeterReadingController::class, 'destroy']);

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/search', [CustomerController::class, 'search']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);

    Route::get('monthly-bills', [MonthlyBillController::class, 'index']);
    Route::get('monthly-bills/usage', [MonthlyBillController::class, 'usage']);
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/test-admin', fn () => response()->json(['message' => 'Kamu admin!']));

    Route::prefix('settings/sop')->group(function () {
        Route::get('/', [SopController::class, 'index']);
        Route::post('/lembaga', [SopController::class, 'updateLembaga']);
        Route::post('/pasang-baru', [SopController::class, 'updatePasangBaru']);
        Route::post('/sistem-tagihan', [SopController::class, 'updateSistemTagihan']);
        Route::post('/logo', [SopController::class, 'updateLogo']);
        Route::post('/whatsapp', [SopController::class, 'updateWhatsapp']);
    });

    Route::apiResource('users', UserController::class);
    Route::apiResource('villages', VillageController::class);

    Route::get('/customers/search', [CustomerController::class, 'search']);

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::put('/customers/{id}', [CustomerController::class, 'update']);
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);

    Route::apiResource('installation-packages', InstallationPackageController::class);
    Route::apiResource('installation-packages.water-tariff-blocks', WaterTariffBlockController::class);

    Route::post('installation-tickets', [InstallationTicketController::class, 'store']);
    Route::put('installation-tickets/{id}/register', [InstallationTicketController::class, 'registerInstallation']);
    Route::patch('installation-tickets/{installationTicket}/transition', [InstallationTicketController::class, 'transition']);
    Route::post('installation-tickets/{installationTicket}/payment', [PaymentController::class, 'store']);
    Route::post('installation-tickets/{installationTicket}/activate', [ActivationController::class, 'activate']);

    // Survey CRUD (Admin)
    Route::get('survey-results', [SurveyResultController::class, 'index']);
    Route::get('survey-results/{id}', [SurveyResultController::class, 'show']);
    Route::put('survey-results/{id}', [SurveyResultController::class, 'update']);
    Route::post('survey-results/{id}', [SurveyResultController::class, 'update']);
    Route::delete('survey-results/{id}', [SurveyResultController::class, 'destroy']);

    // Trouble report management
    Route::get('trouble-reports', [TroubleReportController::class, 'index']);
    Route::get('trouble-reports/{id}', [TroubleReportController::class, 'show']);
    Route::patch('trouble-reports/{id}/status', [TroubleReportController::class, 'updateStatus']);

    Route::get('bills/recap', [BillingController::class, 'recap']);
    Route::post('bills/generate', [BillingController::class, 'generate']);
    Route::get('bills/{monthlyBill}', [BillingController::class, 'show']);

    // BARU : Rekap riwayat pemakaian air bulanan pelanggan
    Route::get('monthly-bills/usage', [MonthlyBillController::class, 'usage']);

    Route::get('monthly-bills', [MonthlyBillController::class, 'index']);
    Route::get('monthly-bills/{id}', [MonthlyBillController::class, 'show']);

    Route::post('monthly-bills/{id}/pay', [MonthlyBillController::class, 'pay']);
    Route::post('monthly-bills/generate', [MonthlyBillController::class, 'generate']);

    Route::get('reports/installations', [InstallationTicketController::class, 'report']);
    Route::get('reports/bills', [MonthlyBillController::class, 'report']);
    Route::get('reports/billing', [ReportController::class, 'billing']);
    Route::get('reports/installation', [ReportController::class, 'installation']);
    Route::get('reports/billing/export-csv', [ReportController::class, 'exportBillingCsv']);
    Route::get('reports/billing/export-pdf', [ReportController::class, 'exportBillingPdf']);
    Route::get('reports/installation/export-csv', [ReportController::class, 'exportInstallationCsv']);
    Route::get('reports/installation/export-pdf', [ReportController::class, 'exportInstallationPdf']);
});

/*
|--------------------------------------------------------------------------
| Surveyor & Admin Routes (Survey Submission)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin,surveyor'])->group(function () {

    Route::post('installation-tickets/{installationTicket}/survey', [SurveyResultController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Teknisi & Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin,teknisi'])->group(function () {
    Route::get('/test-teknisi', fn () => response()->json(['message' => 'Kamu teknisi!']));

    Route::post('installation-tickets/{installationTicket}/installation-result', [InstallationResultController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Pelanggan Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:pelanggan'])->group(function () {
    Route::get('/test-pelanggan', fn () => response()->json(['message' => 'Kamu pelanggan!']));

    Route::get('/pelanggan/dashboard', [PelangganPortalController::class, 'dashboard']);
    Route::get('/pelanggan/bill-detail/{id?}', [PelangganPortalController::class, 'billDetail']);
    Route::get('/pelanggan/bill-history', [PelangganPortalController::class, 'billHistory']);
    Route::get('/pelanggan/profile', [PelangganPortalController::class, 'profile']);

    Route::post('/pelanggan/trouble-report', [TroubleReportController::class, 'store']);
});
