<?php

namespace App\Providers;

use App\Models\BillPayment;
use App\Models\Customer;
use App\Models\MonthlyBill;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Observers\PaymentObserver;
use App\Observers\TransactionObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiters();
        Relation::enforceMorphMap([
            'payment' => Payment::class,
            'monthly_bill' => MonthlyBill::class,
            'customer' => Customer::class,
            'user' => User::class,
            'bill_payment' => BillPayment::class,
        ]);

        Transaction::observe(TransactionObserver::class);
        Payment::observe(PaymentObserver::class);
    }

    /**
     * Batasi percobaan brute-force pada endpoint autentikasi.
     *
     * Tanpa limiter ini, POST /login terbuka penuh: penyerang bisa
     * mencoba kombinasi email/password tanpa batas. Rate limit dihitung
     * per kombinasi email+IP supaya satu penyerang tidak bisa mengunci
     * akun orang lain dengan sengaja (keyBy email saja = DoS akun).
     */
    private function configureRateLimiters(): void
    {
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return Limit::perMinute(5)->by(
                $email.'|'.$request->ip()
            );
        });

        // Lapisan kedua per-IP, untuk menangkap penyerang yang memakai
        // banyak email berbeda dari satu mesin.
        RateLimiter::for('login-ip', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        // Endpoint berat yang menyimpan file, supaya tidak bisa dipakai
        // memenuhi disk atau membebani server dengan request beruntun.
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Dipakai oleh throttleApi() di bootstrap/app.php, yang memasang
        // middleware `throttle:api` ke seluruh grup api. Tanpa daftar ini
        // setiap request yang tidak bisa dilayani limiter akan 500 dengan
        // MissingRateLimiterException, bukan 429.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });
    }
}
