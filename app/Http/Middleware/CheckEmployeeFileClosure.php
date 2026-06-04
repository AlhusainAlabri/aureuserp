<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckEmployeeFileClosure
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $user->loadMissing(['roles', 'employee']);

        $employee = $user->employee;

        if ($employee && (bool) $employee->is_closed) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('hr-extensions::employee.account_closed'),
                ], 403);
            }

            auth()->logout();

            return redirect()
                ->route('filament.admin.auth.login')
                ->with('danger', __('hr-extensions::employee.account_closed'));
        }

        return $next($request);
    }
}
