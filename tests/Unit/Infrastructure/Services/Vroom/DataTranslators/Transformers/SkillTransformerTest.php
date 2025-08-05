<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Services\Vroom\DataTranslators\Transformers;

use App\Domain\RouteOptimization\ValueObjects\Skill;
use App\Infrastructure\Services\Vroom\DataTranslators\Transformers\SkillTransformer;
use Tests\TestCase;

class SkillTransformerTest extends TestCase
{
    /**
     * @test
     */
    public function transform_skill(): void
    {
        $actual = (new SkillTransformer())->transform(new Skill(Skill::INITIAL_SERVICE));
        $this->assertEquals(1001, $actual);
    }
}
