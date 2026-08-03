<?php

namespace App\Http\Middleware;

use App\Support\Performance\SlowQueryRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordSlowQueries
{
    public function __construct(
        private readonly SlowQueryRecorder $recorder,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->recorder->beginRequest($request);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $this->recorder->finishRequest($response);
    }
}
