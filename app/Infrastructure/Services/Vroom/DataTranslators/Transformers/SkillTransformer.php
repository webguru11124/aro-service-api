<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Vroom\DataTranslators\Transformers;

use App\Domain\RouteOptimization\ValueObjects\Skill;

class SkillTransformer
{
    public function transform(Skill $skill): int
    {
        return $skill->value;
    }
}
