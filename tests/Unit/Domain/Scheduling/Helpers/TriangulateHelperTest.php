<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Scheduling\Helpers;

use App\Domain\Scheduling\Helpers\TriangulateHelper;
use App\Domain\Scheduling\ValueObjects\Point;
use Tests\TestCase;

class TriangulateHelperTest extends TestCase
{
    /**
     * @test
     */
    public function it_triangulates_set_of_points(): void
    {
        $helper = new TriangulateHelper();

        $result = $helper->triangulate(collect([
            new Point(0, 0),
            new Point(1, 0),
            new Point(0, 1),
        ]));

        $this->assertCount(1, $result);
    }

    /**
     * @test
     */
    public function it_returns_empty_collection_when_there_are_less_than_three_points(): void
    {
        $helper = new TriangulateHelper();

        $result = $helper->triangulate(collect([
            new Point(0, 0),
            new Point(1, 0),
        ]));

        $this->assertCount(0, $result);
    }
}
