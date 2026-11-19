<?php

use Illuminate\Support\Facades\Context;

test('an incoming traceparent keeps its trace id', function () {
    $traceId = '4bf92f3577b34da6a3ce929d0e0e4736';

    $response = $this->get('/', ['traceparent' => "00-{$traceId}-00f067aa0ba902b7-01"]);

    expect($response->headers->get('traceparent'))->toMatch("/^00-{$traceId}-[0-9a-f]{16}-01$/")
        ->and(Context::get('trace_id'))->toBe($traceId)
        ->and(Context::get('parent_span_id'))->toBe('00f067aa0ba902b7');
});

test('a missing or malformed traceparent starts a new trace', function (?string $header) {
    $response = $this->get('/', $header === null ? [] : ['traceparent' => $header]);

    expect($response->headers->get('traceparent'))->toMatch('/^00-[0-9a-f]{32}-[0-9a-f]{16}-01$/')
        ->and(Context::get('trace_id'))->not->toBe(str_repeat('0', 32))
        ->and(Context::get('parent_span_id'))->toBeNull();
})->with([
    'missing' => null,
    'garbage' => 'not-a-trace',
    'all-zero trace id' => '00-'.str_repeat('0', 32).'-00f067aa0ba902b7-01',
]);

test('log records carry the trace and service in extra', function () {
    $path = storage_path('logs/test-trace.json');
    @unlink($path);
    config(['logging.channels.json.path' => $path]);

    $this->get('/');
    logger()->channel('json')->info('hello');

    $record = json_decode(trim(file_get_contents($path)), true);

    expect($record['message'])->toBe('hello')
        ->and($record['extra']['trace_id'])->toBe(Context::get('trace_id'))
        ->and($record['extra']['service'])->toBe('web');

    unlink($path);
});
