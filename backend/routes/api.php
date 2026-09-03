<?php

use App\Http\Controllers\ActivationController;
use App\Http\Controllers\AmountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\JenisTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EbudgetingController;
use App\Http\Controllers\GenerateAmountController;
use App\Http\Controllers\InstallationPackageController;
use App\Http\Controllers\InstallationResultController;
use App\Http\Controllers\InstallationTicketController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\JurnalUmumController;
use App\Http\Controllers\MeterReadingController;
use App\Http\Controllers\MonthlyBillController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PelangganPortalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SopController;
use App\Http\Controllers\SurveyResultController;
use App\Http\Controllers\TroubleReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TutupBukuController;
use App\Http\Controllers\TunggakanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VillageController;
use App\Http\Controllers\PelaporanController;
use App\Http\Controllers\WaterTariffBlockController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\AlokasiLabaController;
use App\Http\Controllers\KomisiSPSController;
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


// Authenticated Routes (Semua Role)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateProfile']);
    Route::put('/me/password', [AuthController::class, 'updatePassword']);
    Route::post('/me/avatar', [AuthController::class, 'uploadAvatar']);

    Route::get('settings/kecamatan', [SettingController::class, 'getKecamatan']);
    Route::get('settings/desa', [SettingController::class, 'getDesa']);
    Route::get('settings/payment-mode', [SettingController::class, 'getPaymentMode']);
});


//Shared Routes (Bisa diakses Admin & Surveyor)

Route::middleware(['auth:sanctum', 'role:admin,surveyor'])->group(function () {
    // Dipindahkan ke sini agar admin bisa membaca draft & surveyor bisa membaca pending
    Route::get('installation-tickets', [InstallationTicketController::class, 'index']);
    Route::get('installation-tickets/register-dropdown', [InstallationTicketController::class, 'registerDropdown']);
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
    Route::get('dashboard/notification', [DashboardController::class, 'getNotification']);
    Route::post('dashboard/notification/dismiss', [DashboardController::class, 'dismissNotification']);

    Route::get('meter-readings/pending', [MeterReadingController::class, 'index']);
    Route::post('meter-readings', [MeterReadingController::class, 'store']);
    Route::get('meter-readings/{id}', [MeterReadingController::class, 'show']);
    Route::put('meter-readings/{id}', [MeterReadingController::class, 'update']);
    Route::delete('meter-readings/{id}', [MeterReadingController::class, 'destroy']);

    // Read-only shared: list, search & detail pelanggan (tulis khusus admin)
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::get('/customers/search', [CustomerController::class, 'search']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);

    // Read-only shared: daftar tagihan & rekap pemakaian (bayar/hapus khusus admin)
    Route::get('monthly-bills', [MonthlyBillController::class, 'index']);
    Route::get('monthly-bills/usage', [MonthlyBillController::class, 'usage']);

    // Teknisi: lihat daftar pelanggan suspended + konfirmasi restore (setelah bayar oleh admin)
    Route::get('monthly-bills/suspended', [MonthlyBillController::class, 'suspended']);
    Route::post('customers/{id}/restore', [MonthlyBillController::class, 'restoreCustomer']);
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

    Route::get('amount', [AmountController::class, 'show']);
    Route::get('accounts-with-saldo', [AmountController::class, 'accountsWithSaldo']);

    Route::apiResource('users', UserController::class);
    Route::apiResource('villages', VillageController::class);

    // Customer write ops (CRUD) khusus admin
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::put('/customers/{id}', [CustomerController::class, 'update']);
    Route::delete('/customers/{id}', [CustomerController::class, 'destroy']);

    Route::apiResource('installation-packages', InstallationPackageController::class);
    Route::apiResource('installation-packages.water-tariff-blocks', WaterTariffBlockController::class);

    Route::post('installation-tickets', [InstallationTicketController::class, 'store']);
    Route::patch('installation-tickets/{installationTicket}/transition', [InstallationTicketController::class, 'transition']);
    Route::get('installation-tickets-unpaid', [InstallationTicketController::class, 'unpaidTickets']);
    Route::post('installation-tickets/{installationTicket}/payment', [PaymentController::class, 'store']);
    Route::post('installation-tickets/{installationTicket}/advance-stage', [InstallationTicketController::class, 'advanceStage']);
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

    // Tagihan bulanan — tulis hanya admin (baca sudah di grup admin+teknisi)
    Route::get('monthly-bills/{id}', [MonthlyBillController::class, 'show']);
    Route::delete('monthly-bills/{id}', [MonthlyBillController::class, 'destroy']);
    Route::post('monthly-bills/generate', [MonthlyBillController::class, 'generate']);

    Route::get('reports/installations', [InstallationTicketController::class, 'report']);
    Route::get('reports/bills', [MonthlyBillController::class, 'report']);
    Route::get('reports/billing', [ReportController::class, 'billing']);
    Route::get('reports/installation', [ReportController::class, 'installation']);
    Route::get('reports/billing/export-csv', [ReportController::class, 'exportBillingCsv']);
    Route::get('reports/billing/export-pdf', [ReportController::class, 'exportBillingPdf']);
    Route::get('reports/installation/export-csv', [ReportController::class, 'exportInstallationCsv']);
    Route::get('reports/installation/export-pdf', [ReportController::class, 'exportInstallationPdf']);

    //transaction routes
    Route::get('transactions/saldo-akun', [TransactionController::class, 'saldoAkun']);
    Route::get('transactions/buku-besar', [TransactionController::class, 'bukuBesar']);
    Route::apiResource('transactions', TransactionController::class);
    Route::get('jenis-transactions', [JenisTransactionController::class, 'index']);
    Route::post('generate-amount', [GenerateAmountController::class, 'generate']);
    Route::post('tunggakan/generate', [TunggakanController::class, 'generate']);
    Route::get('accounts', [AccountController::class, 'index']);
    Route::get('accounts/by-level/{level}', [AccountController::class, 'byLevel']);
    Route::get('amount/total-saldo', [AmountController::class, 'getTotalSaldo']);

    // Inventaris (CRUD + integrasi jurnal umum)
    Route::apiResource('inventaris', InventoryController::class);
    Route::get('transaksi/jurnal-umum/form', [JurnalUmumController::class, 'form']);
    Route::post('transaksi/inventaris', [JurnalUmumController::class, 'storeInventaris']);
    Route::post('transaksi/inventaris/{inventory}/hapus', [JurnalUmumController::class, 'storeHapusInventaris']);

    //tutup buku routes
    Route::get('tutup-buku/check/{year}', [TutupBukuController::class, 'check']);
    Route::get('tutup-buku/accounts/{year}', [TutupBukuController::class, 'accountsWithSaldo']);
    Route::post('tutup-buku/close', [TutupBukuController::class, 'close']);
    Route::get('tutup-buku/history', [TutupBukuController::class, 'history']);
    Route::get('tutup-buku/config', [TutupBukuController::class, 'getConfig']);
    Route::post('tutup-buku/config', [TutupBukuController::class, 'saveConfig']);

    //alokasi laba routes
    Route::get('alokasi-laba/check/{year}', [AlokasiLabaController::class, 'check']);
    Route::post('alokasi-laba/calculate', [AlokasiLabaController::class, 'calculate']);
    Route::post('alokasi-laba/save', [AlokasiLabaController::class, 'save']);
    Route::get('alokasi-laba/config', [AlokasiLabaController::class, 'getConfig']);
    Route::post('alokasi-laba/config', [AlokasiLabaController::class, 'saveConfig']);
    Route::get('alokasi-laba/accounts', [AlokasiLabaController::class, 'accountsForAllocation']);

    //e-budgeting routes
    Route::get('ebudgeting/check-exists', [EbudgetingController::class, 'checkExists']);
    Route::post('ebudgeting/bulk', [EbudgetingController::class, 'bulkStore']);
    Route::apiResource('ebudgeting', EbudgetingController::class)->whereNumber('ebudgeting');

    Route::apiResource('jenis-transactions', JenisTransactionController::class);

    // Komisi SPS
    Route::get('komisi-sps/cash-accounts', [KomisiSPSController::class, 'cashAccounts']);
    Route::get('komisi-sps/penerima-komisi', [KomisiSPSController::class, 'penerimaKomisi']);
    Route::get('komisi-sps/pelanggan-unpaid', [KomisiSPSController::class, 'pelangganWithUnpaid']);
    Route::get('komisi-sps/unpaid-by-customer', [KomisiSPSController::class, 'unpaidByCustomer']);
    Route::post('komisi-sps', [KomisiSPSController::class, 'store']);

    //
    Route::get('pelaporan/sub-laporan/{file}', [PelaporanController::class, 'subLaporan']);
    Route::get('pelaporan', [PelaporanController::class, 'index']);
    Route::post('pelaporan/preview', [PelaporanController::class, 'preview']);
    Route::post('pelaporan/excel', [PelaporanController::class, 'exportExcel']);
    Route::post('pelaporan/simpan-saldo', [PelaporanController::class, 'simpanSaldo']);
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

// Pelanggan Routes
Route::middleware(['auth:sanctum', 'role:pelanggan'])->group(function () {
    Route::get('/test-pelanggan', fn () => response()->json(['message' => 'Kamu pelanggan!']));

    Route::get('/pelanggan/dashboard', [PelangganPortalController::class, 'dashboard']);
    Route::get('/pelanggan/bill-detail/{id?}', [PelangganPortalController::class, 'billDetail']);
    Route::get('/pelanggan/bill-history', [PelangganPortalController::class, 'billHistory']);
    Route::get('/pelanggan/profile', [PelangganPortalController::class, 'profile']);

    Route::post('/pelanggan/trouble-report', [TroubleReportController::class, 'store']);
});
