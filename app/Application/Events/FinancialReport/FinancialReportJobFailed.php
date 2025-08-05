<?php

declare(strict_types=1);

namespace App\Application\Events\FinancialReport;

use App\Application\Events\FailedEvent;

class FinancialReportJobFailed extends AbstractFinancialReportJobEvent implements FailedEvent
{
    public function getException(): \Throwable
    {
        return $this->exception;
    }
}
