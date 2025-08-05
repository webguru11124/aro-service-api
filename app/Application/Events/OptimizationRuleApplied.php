<?php

declare(strict_types=1);

namespace App\Application\Events;

use App\Domain\Contracts\OptimizationRule;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OptimizationRuleApplied
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly OptimizationRule $rule)
    {
    }
}
