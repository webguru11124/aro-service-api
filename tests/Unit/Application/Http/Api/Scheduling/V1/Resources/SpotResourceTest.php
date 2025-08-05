<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Http\Api\Scheduling\V1\Resources;

use App\Application\Http\Api\Scheduling\V1\Resources\SpotResource;
use App\Infrastructure\Services\PestRoutes\Entities\Spot;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\SpotFactory;

class SpotResourceTest extends TestCase
{
    private Spot $spot;
    private SpotResource $resource;
    private Request|MockInterface $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = \Mockery::mock(Request::class);

        /** @var Spot $spot */
        $this->spot = SpotFactory::make();
        $this->resource = new SpotResource($this->spot);
    }

    /**
     * @test
     */
    public function it_creates_expected_array_representation_of_resource(): void
    {
        $array = $this->resource->toArray($this->request);

        $this->assertEquals(
            [
                'spot_id' => $this->spot->getId(),
                'date' => $this->spot->getTimeWindow()->getStartAt()->toDateString(),
                'window' => $this->spot->getWindow(),
                'is_aro_spot' => $this->spot->isAroSpot(),
            ],
            $array
        );
    }
}
