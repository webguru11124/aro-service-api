<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\SharedKernel\Entities;

use Tests\TestCase;
use App\Domain\SharedKernel\Entities\Skill;

class SkillTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_skill_with_correct_descriptor(): void
    {
        $skill = new Skill('Project Management');

        $this->assertEquals('Project Management', $skill->getDescriptor());
    }
}
