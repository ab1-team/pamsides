<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

class TunggakanController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tanggal' => 'nullable|date_format:Y-m-d',
            'force'   => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $start   = microtime(true);
        $tanggal = $request->input('tanggal', date('Y-m-d'));
        $force   = $request->boolean('force');

        $opts = ['--tanggal' => $tanggal];
        if ($force) {
            $opts['--force'] = true;
        }

        $exit   = Artisan::call('billing:generate-overdue-transactions', $opts);
        $output = Artisan::output();

        $duration = (int) ((microtime(true) - $start) * 1000);

        return response()->json([
            'success'     => $exit === 0,
            'tanggal'     => $tanggal,
            'force'       => $force,
            'duration_ms' => $duration,
            'output'      => trim($output),
        ]);
    }
}