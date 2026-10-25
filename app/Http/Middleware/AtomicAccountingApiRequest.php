<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Runs each write request to the accounting API in one database transaction. The plugin controllers catch errors
 * (a period-lock refusal included) and answer with a 4xx after part of the work was written, so the transaction is
 * rolled back whenever the answer is not successful.
 */
class AtomicAccountingApiRequest
{
    /**
     * @var array<int, string>
     */
    public const PATHS = ['admin/api/v1/accounts/*'];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe() || ! $request->is(...self::PATHS)) {
            return $next($request);
        }

        DB::beginTransaction();

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }

        $response->getStatusCode() >= 400 ? DB::rollBack() : DB::commit();

        return $response;
    }
}
