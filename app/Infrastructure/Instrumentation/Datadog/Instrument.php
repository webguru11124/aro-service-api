<?php

declare(strict_types=1);

namespace App\Infrastructure\Instrumentation\Datadog;

use Throwable;

class Instrument
{
    /**
     * Creates a Datadog Error/Issue for Error Tracking: https://docs.datadoghq.com/tracing/error_tracking/
     * Most useful when you want to create a Datadog error even when you have already handled the exception
     * i.e. 5xx Http responses
     *
     * @param Throwable $throwable
     *
     * @return void
     */
    public static function error(Throwable $throwable): void
    {
        if (self::canGetActiveSpan()) {
            $span = \DDTrace\active_span();
            $span->meta['error.msg'] = $throwable->getMessage();
            $span->meta['error.type'] = get_class($throwable);
            $span->meta['error.stack'] = $throwable->getTraceAsString();
        }
    }

    private static function canGetActiveSpan(): bool
    {
        return function_exists('\DDTrace\active_span') && \DDTrace\active_span() != null;
    }

    /**
     * Initializes new span_id/trace_id for job handler
     *
     * https://docs.datadoghq.com/tracing/guide/trace-php-cli-scripts/?s=DD_TRACE_CLI_ENABLED#long-running-cli-scripts
     *
     * @return void
     */
    public static function traceOptimizeRoutesJob(): void
    {
        if (function_exists('\DDTrace\trace_method')) {
            \DDTrace\trace_method('OptimizeRoutesJob', 'handle', function (\DDTrace\SpanData $span) {
                $span->meta['message.optimization_trace_id'] = random_int(1000, 9999);
            });
        }
    }
}
