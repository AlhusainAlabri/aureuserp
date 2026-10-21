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
        if (! PluginManagerUi::isHidden()) {
            return $next($request);
        }

        $path = '/'.trim($request->path(), '/');

        if (! PluginManagerUi::isPluginsPath($path)) {
            return $next($request);
        }

        if (PluginManagerUi::queryKeyMatches($request)) {
            PluginManagerUi::remember($request);

            if ($request->isMethod('GET') && $request->query->has(PluginManagerUi::QUERY_KEY)) {
                return redirect()->to($request->url());
            }

            return $next($request);
        }

        if (PluginManagerUi::isRemembered($request)) {
            return $next($request);
        }

        abort(404);
    }
}
