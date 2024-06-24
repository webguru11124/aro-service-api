<?php

declare(strict_types=1);

namespace Tests\Traits;

trait RuleGetNameAndDescription
{
    /**
     * @test
     */
    public function it_gets_name_of_rule(): void
    {
        $this->assertNotEmpty($this->rule->name());
    }

    /**
     * @test
     */
    public function it_gets_description_of_rule(): void
    {
        $this->assertNotEmpty($this->rule->description());
    }
}
