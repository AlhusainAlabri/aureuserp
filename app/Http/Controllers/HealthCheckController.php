<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $token = (string) config('operations.public_health.token');

        if ($token !== '' && request()->header('X-Health-Token') !== $token) {
            abort(403);
        }

        try {
            DB::connection()->getPdo();

            return response()->json(['status' => 'ok']);
        } catch (\Throwable) {
            return response()->json(['status' => 'fail'], 503);
        }
    }
}
