<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Activitylog\Facades\LogBatch;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Store request metadata in service container for activity logging
        app()->bind('activitylog.ip', fn() => $request->ip());
        app()->bind('activitylog.user_agent', fn() => $request->userAgent());

        // Start a batch for grouping related operations
        LogBatch::startBatch();

        $response = $next($request);

        // End the batch after request is processed
        LogBatch::endBatch();

        return $response;
    }
}
