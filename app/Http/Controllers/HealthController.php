<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Health check untuk uptime monitor (NFR-03, BL-08).
 * Endpoint: GET /health — publik, tanpa autentikasi.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $dbOk = false;

        try {
            DB::select('select 1');
            $dbOk = true;
        } catch (\Throwable) {
            // database bermasalah — status tetap dilaporkan di bawah
        }

        return response()->json([
            'ok' => $dbOk,
            'app' => config('app.name'),
            'database' => $dbOk ? 'up' : 'down',
            'time' => now()->toIso8601String(),
        ], $dbOk ? 200 : 503);
    }
}
