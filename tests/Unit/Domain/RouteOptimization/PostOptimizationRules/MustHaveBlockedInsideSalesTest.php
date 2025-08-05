<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\PostOptimizationRules;

use App\Domain\RouteOptimization\PostOptimizationRules\MustHaveBlockedInsideSales;
use Tests\TestCase;

class MustHaveBlockedInsideSalesTest extends TestCase
{
    private MustHaveBlockedInsideSales $mustHaveBlockedInsideSales;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mustHaveBlockedInsideSales = new MustHaveBlockedInsideSales();
    }

    /**
     * @test
     *
     * ::getName
     */
    public function it_returns_rule_id(): void
    {
        $result = $this->mustHaveBlockedInsideSales->id();

        $this->assertEquals('MustHaveBlockedInsideSales', $result);
    }

    /**
     * @test
     *
     * ::getName
     */
    public function it_returns_rule_name(): void
    {
        $result = $this->mustHaveBlockedInsideSales->name();

        $this->assertNotEmpty($result);
    }

    /**
     * @test
     *
     * ::getDescription
     */
    public function it_returns_rule_description(): void
    {
        $result = $this->mustHaveBlockedInsideSales->description();

        $this->assertNotEmpty($result);
    }
}
