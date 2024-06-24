<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\ValueObjects;

use App\Domain\RouteOptimization\ValueObjects\RuleExecutionResult;
use Tests\TestCase;

class RuleExecutionResultTest extends TestCase
{
    /**
     * @test
     */
    public function it_returns_expected_getters(): void
    {
        $result = new RuleExecutionResult(
            'test_rule',
            'name',
            'description',
            true,
            true
        );

        $this->assertEquals('test_rule', $result->getRuleId());
        $this->assertEquals('name', $result->getRuleName());
        $this->assertEquals('description', $result->getRuleDescription());
        $this->assertTrue($result->isTriggered());
        $this->assertTrue($result->isApplied());
    }
}
