<?php

namespace App\Providers;

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
        ]);
    }
}
