<?php

declare(strict_types=1);

namespace App\Application\Listeners\Log;

use App\Application\Events\OptimizationRuleApplied;
use Illuminate\Support\Facades\Log;

class LogOptimizationRuleApplying
{
    /**
     * @param OptimizationRuleApplied $event
     *
     * @return void
     */
    public function handle(OptimizationRuleApplied $event): void
    {
        Log::info(sprintf('Applying rule [%s]', $event->rule->name()));
    }
}
