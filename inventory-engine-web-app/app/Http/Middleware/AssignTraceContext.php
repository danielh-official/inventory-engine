<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accepts or starts a W3C trace (https://www.w3.org/TR/trace-context/) and stores it in
 * Laravel's Context, which adds it to every log record and carries it into queued jobs.
 */
class AssignTraceContext
{
    private const TRACEPARENT = '/^00-([0-9a-f]{32})-([0-9a-f]{16})-[0-9a-f]{2}$/';

    public function handle(Request $request, Closure $next): Response
    {
        $header = strtolower((string) $request->header('traceparent'));

        $valid = preg_match(self::TRACEPARENT, $header, $m) === 1 && $m[1] !== str_repeat('0', 32);

        $traceId = $valid ? $m[1] : bin2hex(random_bytes(16));
        $spanId = bin2hex(random_bytes(8));

        Context::add([
            'trace_id' => $traceId,
            'span_id' => $spanId,
            'parent_span_id' => $valid ? $m[2] : null,
        ]);

        $response = $next($request);
        $response->headers->set('traceparent', "00-{$traceId}-{$spanId}-01");

        return $response;
    }
}
