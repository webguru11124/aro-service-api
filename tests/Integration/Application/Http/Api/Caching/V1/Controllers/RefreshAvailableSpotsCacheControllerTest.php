<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Caching\V1\Controllers;

use App\Application\Http\Api\Caching\Controllers\RefreshAvailableSpotsCacheController;
use App\Application\Jobs\RefreshAvailableSpotsCacheJob;
use App\Domain\Contracts\Queries\Office\GetAllOfficesQuery;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Tools\Factories\OfficeFactory;

/**
 * @coversDefaultClass RefreshAvailableSpotsCacheController
 */
class RefreshAvailableSpotsCacheControllerTest extends TestCase
{
    private const ROUTE_NAME = 'available-spots-cache.refresh';

    private GetAllOfficesQuery|MockInterface $mockOfficesQuery;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();

        $this->mockOfficesQuery = Mockery::mock(GetAllOfficesQuery::class);
        $this->instance(GetAllOfficesQuery::class, $this->mockOfficesQuery);
    }

    /**
     * @test
     */
    public function it_returns_expected_202(): void
    {
        $this->mockOfficesQuery
            ->shouldReceive('get')
            ->once()
            ->andReturn(collect([
                OfficeFactory::make(),
            ]));

        $response = $this->putJson(
            route(self::ROUTE_NAME),
        );

        $response->assertAccepted();
        $response->assertJsonPath('_metadata.success', true);
        Queue::assertPushed(RefreshAvailableSpotsCacheJob::class);
    }

    /**
     * @test
     */
    public function it_returns_400_when_invalid_parameters_passed(): void
    {
        $this->mockOfficesQuery
            ->shouldReceive('get')
            ->never();

        $response = $this->putJson(
            route(self::ROUTE_NAME),
            [
                'office_ids' => 'invalid',
            ],
        );

        $response->assertBadRequest();
        Queue::assertNotPushed(RefreshAvailableSpotsCacheJob::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->mockOfficesQuery);
    }
}
