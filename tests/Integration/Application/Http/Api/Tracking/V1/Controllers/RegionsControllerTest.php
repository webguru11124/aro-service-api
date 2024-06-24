<?php

declare(strict_types=1);

namespace Tests\Integration\Application\Http\Api\Tracking\V1\Controllers;

use App\Application\Http\Api\Tracking\V1\Controllers\RegionsController;
use App\Infrastructure\Queries\Static\Office\StaticGetAllRegionsQuery;
use Mockery;
use Tests\TestCase;
use Tests\Tools\JWTAuthTokenHelper;

/**
 * @coversDefaultClass RegionsController
 */
class RegionsControllerTest extends TestCase
{
    use JWTAuthTokenHelper;

    private const ROUTE_NAME = 'tracking.regions.index';

    /** @var string[] */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->headers = [
            'Authorization' => 'Bearer ' . $this->generateValidJwtToken(),
        ];
    }

    /**
     * @test
     */
    public function it_returns_list_of_all_regions(): void
    {
        $response = $this
            ->withHeaders($this->getHeaders())
            ->getJson(route(self::ROUTE_NAME));

        $response->assertOk();
        $response->assertJsonStructure([
            '_metadata' => ['success'],
        ]);
    }

    /**
     * @test
     */
    public function it_handles_exceptions(): void
    {
        $officesRegionsQueryMock = Mockery::mock(StaticGetAllRegionsQuery::class);
        $officesRegionsQueryMock->shouldReceive('get')->andThrow(new \Exception('Test exception'));

        $this->instance(StaticGetAllRegionsQuery::class, $officesRegionsQueryMock);

        $response = $this
            ->withHeaders($this->getHeaders())
            ->getJson(route(self::ROUTE_NAME));

        $response->assertInternalServerError();
        $response->assertJson([
            '_metadata' => [
                'success' => false,
            ],
            'result' => [
                'message' => 'An error was encountered while attempting to process the request. Please contact the System Administrator.',
            ],
        ]);
    }

    private function getHeaders(): array
    {
        return $this->headers;
    }
}
