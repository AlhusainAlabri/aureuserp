<?php

namespace App\Http\Middleware;

use App\Support\PluginManager\PluginManagerUi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DenyPluginsUiAccess
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            PluginManagerUi::isHidden()
            && str_starts_with(trim($request->path(), '/'), 'admin/plugins')
        ) {
            abort(403);
        }

        return $next($request);
    }
}
