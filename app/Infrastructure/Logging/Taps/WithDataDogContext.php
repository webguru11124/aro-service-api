<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging\Taps;

use Monolog\Formatter\JsonFormatter;

class WithDataDogContext
{
    private $logger; /** @phpstan-ignore-line */
    private $context; /** @phpstan-ignore-line */

    /**
     * Customize the given logger instance.
     *
     * @param  \Illuminate\Log\Logger  $logger
     *
     * @return void
     */
    public function __invoke($logger)
    {
        $this->setLogger($logger);

        if ($this->canGetDatadogContext()) {
            $this->setDataDogContext();
        } else {
            return; // Data dog context is not defined so exit
        }

        if ($this->loggerHasJsonFormatter()) {
            $this->addDataDogContextToJsonFormattedLogRecord();

            // Default to Monolog LineFormatter
        } else {
            $this->addDataDogContextToLineFormattedLogRecord();
        }
    }

    /* @phpstan-ignore-next-line */
    private function setLogger($logger): void
    {
        $this->logger = $logger;
    }

    private function canGetDatadogContext(): bool
    {
        return function_exists('\DDTrace\current_context');
    }

    private function setDataDogContext(): void
    {
        // Need the current Datadog context for the trace_ids and span_ids
        $this->context = \DDTrace\current_context();
    }

    private function loggerHasJsonFormatter(): bool
    {
        $handlers = $this->logger->getHandlers();

        foreach ($handlers as $handler) {
            if (is_a($handler->getFormatter(), JsonFormatter::class)) {
                return true;
            }
        }

        return false;
    }

    private function addDataDogContextToJsonFormattedLogRecord(): void
    {
        $this->logger->pushProcessor(function ($record) {
            // Reference: https://docs.datadoghq.com/tracing/other_telemetry/connect_logs_and_traces/php/
            $record->extra['dd'] = [
                'trace_id' => $this->context['trace_id'],
                'span_id' => $this->context['span_id'],
            ];

            return $record;
        });
    }

    private function addDataDogContextToLineFormattedLogRecord(): void
    {
        $this->logger->pushProcessor(function ($record) {
            // DataDog requires the trace and span IDs to be added to the 'message' portion of the log record
            //   in this format for Line Formatted logs
            // Reference: https://docs.datadoghq.com/tracing/other_telemetry/connect_logs_and_traces/php/
            $record['message'] .= sprintf(
                ' [dd.trace_id=%s dd.span_id=%s]',
                $this->context['trace_id'],
                $this->context['span_id']
            );

            return $record;
        });
    }
}
