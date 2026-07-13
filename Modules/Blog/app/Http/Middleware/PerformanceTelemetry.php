<?php

namespace Modules\Blog\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PerformanceTelemetry
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('app.debug')) {
            return $next($request);
        }

        $queryCount = 0;
        $queryTime = 0;

        DB::listen(function ($query) use (&$queryCount, &$queryTime) {
            $queryCount++;
            $queryTime += $query->time / 1000;
        });

        $startTime = microtime(true);

        /** @var Response $response */
        $response = $next($request);

        $totalTime = round((microtime(true) - $startTime) * 1000, 2);
        $memoryUsed = memory_get_usage(false);
        $peakMemory = memory_get_peak_usage(true);

        $response->headers->set('X-Request-Time', "{$totalTime}ms");
        $response->headers->set('X-Db-Query-Count', (string) $queryCount);
        $response->headers->set('X-Db-Query-Time', round($queryTime * 1000, 2) . 'ms');
        $response->headers->set('X-Memory-Used', $this->formatBytes($memoryUsed));
        $response->headers->set('X-Memory-Peak', $this->formatBytes($peakMemory));

        return $response;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;

        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }
}
