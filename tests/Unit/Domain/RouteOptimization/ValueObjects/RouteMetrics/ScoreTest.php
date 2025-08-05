<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\RouteOptimization\ValueObjects\RouteMetrics;

use App\Domain\RouteOptimization\ValueObjects\RouteMetrics\Score;
use Tests\TestCase;

class ScoreTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_score(): void
    {
        $testValue = random_int(Score::MIN_POSSIBLE_SCORE, Score::MAX_POSSIBLE_SCORE);
        $score = new Score($testValue);

        $this->assertEquals($testValue, $score->value());
    }

    /**
     * @test
     */
    public function it_throw_exception_when_given_score_value_is_below_min(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Score(Score::MIN_POSSIBLE_SCORE - 1);
    }

    /**
     * @test
     */
    public function it_throw_exception_when_given_score_value_is_above_max(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Score(Score::MAX_POSSIBLE_SCORE + 1);
    }
}
