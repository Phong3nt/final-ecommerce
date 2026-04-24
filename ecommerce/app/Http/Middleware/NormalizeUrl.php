<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeUrl
{
    /**
     * Redirect requests with double (or more) slashes in the path to the normalized URL.
     * e.g. /products//search → /products/search
     *      //login           → /login
     */
    public function handle(Request $request, Closure $next): Response
    {
        $path = $request->getPathInfo();

        // If the path contains consecutive slashes, normalize and redirect
        if (str_contains($path, '//')) {
            $normalized = preg_replace('#/+#', '/', $path);
            $query = $request->getQueryString();
            $url = $normalized . ($query ? '?' . $query : '');

            return redirect($url, 301);
        }

        return $next($request);
    }
}
