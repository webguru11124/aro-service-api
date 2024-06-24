<?php

declare(strict_types=1);

namespace Tests\Traits;

trait ConsecutiveCalls
{
    protected function consecutiveCalls(...$args): callable
    {
        $count = 0;

        return function ($arg) use (&$count, $args) {
            return $arg === $args[$count++];
        };
    }
}
