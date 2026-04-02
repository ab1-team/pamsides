<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class MeterReadingController extends Controller
{
    public function unrecorded()
    {
        $year  = now()->year;
        $month = now()->month;

        $customers = Customer::with(['user', 'ticket.package'])
            ->whereDoesntHave('meterReadings', function ($q) use ($year, $month) {
                $q->where('reading_year', $year)
                  ->where('reading_month', $month);
            })
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'year'      => $year,
                'month'     => $month,
                'total'     => $customers->count(),
                'customers' => $customers,
            ],
        ]);
    }
}
