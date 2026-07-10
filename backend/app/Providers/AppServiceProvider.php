<?php

namespace App\Providers;

use App\Models\Payment;
use App\Models\BillPayment;
use App\Models\Transaction;
use App\Observers\PaymentObserver;
use App\Observers\BillPaymentObserver;
use App\Observers\TransactionObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Relation::enforceMorphMap([
            'payment'      => \App\Models\Payment::class,
            'monthly_bill' => \App\Models\MonthlyBill::class,
            'customer'     => \App\Models\Customer::class,
            'user'         => \App\Models\User::class,
            'bill_payment' => \App\Models\BillPayment::class,
        ]);

        Transaction::observe(TransactionObserver::class);
        Payment::observe(PaymentObserver::class);
        BillPayment::observe(BillPaymentObserver::class);
    }
}
